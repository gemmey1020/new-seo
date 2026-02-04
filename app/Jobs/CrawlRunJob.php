<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Crawl\CrawlRun;
use App\Models\Crawl\CrawlLog;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use DOMDocument;
use DOMXPath;

/**
 * CrawlRunJob - ENGINE CORE EXECUTION WITH EXTRACTION
 * 
 * This job performs REAL crawl execution:
 * 1. Transitions CrawlRun through state machine
 * 2. Discovers URLs starting from seed (homepage)
 * 3. Performs actual HTTP GET requests
 * 4. Extracts SEO signals (Title, Meta, H1, Images)
 * 5. Persists Page and SeoMeta records
 * 6. Writes CrawlLog entries for every request
 * 
 * INVARIANT: Pages are created ONLY by this job's execution.
 */
class CrawlRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $crawlRunId;

    // Configuration limits
    private const MAX_PAGES = 100;
    private const MAX_DEPTH = 3;
    private const REQUEST_DELAY_MS = 100;
    private const TIMEOUT_SECONDS = 10;
    private const MAX_BODY_SIZE = 5 * 1024 * 1024; // 5MB limit for parsing

    /**
     * Create a new job instance.
     */
    public function __construct(int $crawlRunId)
    {
        $this->crawlRunId = $crawlRunId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $run = CrawlRun::find($this->crawlRunId);
        
        if (!$run) {
            Log::error('CrawlRunJob: CrawlRun not found', ['id' => $this->crawlRunId]);
            return;
        }

        if ($run->status !== CrawlRun::STATUS_PENDING) {
            Log::warning('CrawlRunJob: CrawlRun not in pending state', [
                'id' => $run->id,
                'status' => $run->status
            ]);
            return;
        }

        try {
            $run->transitionTo(CrawlRun::STATUS_RUNNING);
            $site = $run->site;
            if (!$site) {
                $run->fail('Site not found');
                return;
            }

            $seedUrl = 'https://' . $site->domain . '/';
            $urlQueue = [['url' => $seedUrl, 'depth' => 0]];
            $visited = [];
            $pagesDiscovered = 0;
            $pagesCrawled = 0;
            $errorsCount = 0;

            while (!empty($urlQueue) && $pagesCrawled < self::MAX_PAGES) {
                $item = array_shift($urlQueue);
                $url = $item['url'];
                $depth = $item['depth'];

                if (isset($visited[$url])) {
                    continue;
                }
                $visited[$url] = true;

                usleep(self::REQUEST_DELAY_MS * 1000);

                $result = $this->fetchUrl($url, $run);
                $pagesCrawled++;

                if ($result['success']) {
                    // Extract content signals if HTML
                    $extracted = [];
                    Log::info('CrawlRunJob: Checking content extraction', [
                        'url' => $url,
                        'content_type' => $result['content_type'],
                        'is_html' => $result['content_type'] === 'text/html'
                    ]);
                    
                    if ($result['content_type'] === 'text/html') {
                        $extracted = $this->extractContent($result['body'], $url, $result['headers'] ?? []);
                        Log::info('CrawlRunJob: Extracted content', ['keys' => array_keys($extracted)]);
                    }

                    // Upsert Page and SeoMeta
                    $page = $this->upsertPageAndMeta($site->id, $url, $result, $run->id, $depth, $extracted);
                    $pagesDiscovered++;

                    $this->logRequest($run, $page, $url, $result);

                    // Parse links for discovery
                    if ($depth < self::MAX_DEPTH && $result['content_type'] === 'text/html') {
                        $links = $this->parseLinks($result['body'], $url, $site->domain);
                        foreach ($links as $link) {
                            if (!isset($visited[$link])) {
                                $urlQueue[] = ['url' => $link, 'depth' => $depth + 1];
                            }
                        }
                    }
                } else {
                    $errorsCount++;
                    $this->logError($run, $url, $result);
                }

                if ($pagesCrawled % 10 === 0) {
                    $run->update([
                        'pages_discovered' => $pagesDiscovered,
                        'pages_crawled' => $pagesCrawled,
                        'errors_count' => $errorsCount,
                    ]);
                }
            }

            $run->update([
                'pages_discovered' => $pagesDiscovered,
                'pages_crawled' => $pagesCrawled,
                'errors_count' => $errorsCount,
            ]);
            $run->transitionTo(CrawlRun::STATUS_COMPLETED);

            Log::info('CrawlRunJob: Completed', [
                'run_id' => $run->id,
                'pages_discovered' => $pagesDiscovered,
                'pages_crawled' => $pagesCrawled,
            ]);

        } catch (\Exception $e) {
            Log::error('CrawlRunJob: Failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
            $run->error_message = $e->getMessage();
            if ($run->status === CrawlRun::STATUS_RUNNING) {
                $run->transitionTo(CrawlRun::STATUS_FAILED);
            }
        }
    }

    /**
     * Fetch URL and return response data + headers.
     */
    private function fetchUrl(string $url, CrawlRun $run): array
    {
        try {
            $start = microtime(true);
            $response = Http::withUserAgent($run->user_agent)
                ->timeout(self::TIMEOUT_SECONDS)
                ->withOptions(['allow_redirects' => ['track_redirects' => true]])
                ->get($url);
            $duration = round((microtime(true) - $start) * 1000);
            
            return [
                'success' => true,
                'status_code' => $response->status(),
                'response_ms' => $duration,
                'bytes' => strlen($response->body()),
                'content_type' => $this->parseContentType($response->header('Content-Type')),
                'final_url' => $response->effectiveUri()?->__toString() ?? $url,
                'body' => $response->body(),
                'headers' => $response->headers(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 0,
                'error' => $e->getMessage(),
                'response_ms' => 0,
            ];
        }
    }

    /**
     * Extract SEO signals from HTML.
     */
    private function extractContent(string $html, string $url, array $headers): array
    {
        // Skip if too large
        if (strlen($html) > self::MAX_BODY_SIZE) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);

        // 1. Title
        $title = '';
        $nodes = $xpath->query('//title');
        if ($nodes->length > 0) {
            $title = trim($nodes->item(0)->textContent);
        }

        // 2. Meta Description
        $description = '';
        $nodes = $xpath->query('//meta[@name="description"]/@content');
        if ($nodes->length > 0) {
            $description = trim($nodes->item(0)->nodeValue);
        }

        // 3. Robots Meta
        $robotsMeta = '';
        $nodes = $xpath->query('//meta[@name="robots"]/@content');
        if ($nodes->length > 0) {
            $robotsMeta = trim($nodes->item(0)->nodeValue);
        }

        // 4. Canonical
        $canonical = '';
        $nodes = $xpath->query('//link[@rel="canonical"]/@href');
        if ($nodes->length > 0) {
            $canonical = trim($nodes->item(0)->nodeValue);
             // Resolve relative canonical if needed
             // Simple check: if not starts with http, assume relative (could be refined)
             if ($canonical && !str_starts_with($canonical, 'http')) {
                 $base = parse_url($url);
                 $baseUrl = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '');
                 $canonical = $baseUrl . '/' . ltrim($canonical, '/');
             }
        }

        // 5. H1
        $h1Count = 0;
        $h1Text = null;
        $nodes = $xpath->query('//h1');
        $h1Count = $nodes->length;
        if ($h1Count > 0) {
            $h1Text = trim($nodes->item(0)->textContent);
        }

        // 6. Images
        $imageCount = 0;
        $imageSample = [];
        $nodes = $xpath->query('//img[@src]');
        $imageCount = $nodes->length;
        foreach ($nodes as $i => $node) {
            if ($i >= 10) break;
            $src = trim($node->getAttribute('src'));
            if ($src) {
                $imageSample[] = $src;
            }
        }

        // 7. Robots Header
        $robotsHeader = null;
        // Headers might be array of strings or string
        if (isset($headers['X-Robots-Tag'])) {
            $headerVal = $headers['X-Robots-Tag'];
            $robotsHeader = is_array($headerVal) ? implode(', ', $headerVal) : $headerVal;
        } elseif (isset($headers['x-robots-tag'])) {
            $headerVal = $headers['x-robots-tag'];
            $robotsHeader = is_array($headerVal) ? implode(', ', $headerVal) : $headerVal;
        }

        libxml_clear_errors();

        return [
            'title' => substr($title, 0, 255), // Limit length
            'description' => substr($description, 0, 500),
            'robots' => substr($robotsMeta, 0, 255),
            'robots_header' => substr($robotsHeader, 0, 255),
            'canonical_extracted' => $canonical,
            'h1_count' => $h1Count,
            'h1_first_text' => substr($h1Text, 0, 255),
            'image_count' => $imageCount,
            'images_sample_json' => $imageSample,
        ];
    }

    /**
     * Upsert Page AND SeoMeta.
     */
    private function upsertPageAndMeta(int $siteId, string $url, array $result, int $crawlRunId, int $depth, array $extracted): Page
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        
        $page = Page::where('site_id', $siteId)->where('url', $url)->first();

        // Prepare page data
        $pageData = [
            'http_status_last' => $result['status_code'],
            'last_crawled_at' => now(),
            'h1_count' => $extracted['h1_count'] ?? 0,
            'image_count' => $extracted['image_count'] ?? 0,
            'content_bytes' => $result['bytes'] ?? 0,
        ];
        
        if ($page) {
            $page->update($pageData);
        } else {
            $pageData = array_merge($pageData, [
                'site_id' => $siteId,
                'url' => $url,
                'path' => $path,
                'page_type' => $depth === 0 ? 'homepage' : 'general',
                'index_status' => 'unknown',
                'depth_level' => $depth,
                'first_seen_at' => now(),
                'discovered_by_crawl_run_id' => $crawlRunId,
            ]);
            $page = Page::create($pageData);
        }

        // Upsert SeoMetadata
        SeoMeta::updateOrCreate(
            ['page_id' => $page->id],
            [
                'title' => $extracted['title'] ?? null,
                'description' => $extracted['description'] ?? null,
                'robots' => $extracted['robots'] ?? null,
                'robots_header' => $extracted['robots_header'] ?? null,
                'canonical_extracted' => $extracted['canonical_extracted'] ?? null,
                'h1_first_text' => $extracted['h1_first_text'] ?? null,
                'images_sample_json' => $extracted['images_sample_json'] ?? [],
            ]
        );
        
        return $page;
    }

    private function parseContentType(?string $header): string
    {
        if (!$header) return 'unknown';
        $parts = explode(';', $header);
        return trim($parts[0]);
    }

    private function logRequest(CrawlRun $run, Page $page, string $url, array $result): void
    {
        CrawlLog::create([
            'site_id' => $run->site_id,
            'page_id' => $page->id,
            'crawl_run_id' => $run->id,
            'requested_url' => $url,
            'final_url' => $result['final_url'] ?? $url,
            'status_code' => $result['status_code'],
            'response_ms' => $result['response_ms'],
            'bytes' => $result['bytes'] ?? 0,
            'content_type' => $result['content_type'] ?? 'unknown',
            'crawled_at' => now(),
        ]);
    }

    private function logError(CrawlRun $run, string $url, array $result): void
    {
        CrawlLog::create([
            'site_id' => $run->site_id,
            'page_id' => null,
            'crawl_run_id' => $run->id,
            'requested_url' => $url,
            'final_url' => null,
            'status_code' => $result['status_code'] ?? 0,
            'response_ms' => $result['response_ms'] ?? 0,
            'bytes' => 0,
            'content_type' => 'error',
            'notes' => $result['error'] ?? 'Unknown error',
            'crawled_at' => now(),
        ]);
    }

    private function parseLinks(string $html, string $baseUrl, string $domain): array
    {
        if (empty($html)) return [];
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query('//a[@href]');

        $baseParts = parse_url($baseUrl);
        $baseScheme = $baseParts['scheme'] ?? 'https';
        $baseHost = $baseParts['host'] ?? $domain;
        $basePath = $baseParts['path'] ?? '/';

        $links = [];
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if (empty($href) || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, '#')) continue;
            $normalized = $this->normalizeUrl($href, $baseScheme, $baseHost, $basePath);
            if ($normalized && $this->isSameDomain($normalized, $domain)) {
                $links[$normalized] = true;
            }
        }
        libxml_clear_errors();
        return array_keys($links);
    }

    private function normalizeUrl(string $href, string $baseScheme, string $baseHost, string $basePath): ?string
    {
        if (preg_match('/^https?:\/\//', $href)) return $this->cleanUrl($href);
        if (str_starts_with($href, '//')) return $this->cleanUrl($baseScheme . ':' . $href);
        if (str_starts_with($href, '/')) return $this->cleanUrl($baseScheme . '://' . $baseHost . $href);
        $baseDir = rtrim(dirname($basePath), '/');
        return $this->cleanUrl($baseScheme . '://' . $baseHost . $baseDir . '/' . $href);
    }

    private function cleanUrl(string $url): string
    {
        $url = preg_replace('/#.*$/', '', $url);
        $parsed = parse_url($url);
        if (isset($parsed['path']) && $parsed['path'] !== '/' && str_ends_with($parsed['path'], '/')) {
            $parsed['path'] = rtrim($parsed['path'], '/');
        }
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        return $scheme . '://' . $host . $path . $query;
    }

    private function isSameDomain(string $url, string $domain): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host === $domain || $host === 'www.' . $domain || $domain === 'www.' . $host;
    }
}
