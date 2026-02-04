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

                    // Process Links (Persist + Discover)
                    if ($result['content_type'] === 'text/html') {
                        $discoveredUrls = $this->processLinks($page, $result['body'], $url, $site->domain);
                        
                        if ($depth < self::MAX_DEPTH) {
                            foreach ($discoveredUrls as $linkUrl) {
                                if (!isset($visited[$linkUrl])) {
                                    $urlQueue[] = ['url' => $linkUrl, 'depth' => $depth + 1];
                                }
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

            // Post-Crawl Resolution of Internal Links
            $this->resolveLinks($site->id);

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
            'robots_header' => substr((string)$robotsHeader, 0, 255),
            'canonical_extracted' => $canonical,
            'h1_count' => $h1Count,
            'h1_first_text' => substr((string)$h1Text, 0, 255),
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

    /**
     * Parse links for discovery AND persistence.
     * Returns list of discoverable URLs for the queue.
     */
    private function processLinks(Page $page, string $html, string $url, string $domain): array
    {
        if ($page->depth_level >= self::MAX_DEPTH) {
            return [];
        }

        $links = $this->extractLinks($html, $url, $domain);
        
        // Persist Internal Links
        $this->persistLinks($page, $links);

        // Return only URLs for queue (filtering visited is done by caller, but we return normalized list)
        return array_column($links, 'url');
    }

    private function extractLinks(string $html, string $baseUrl, string $domain): array
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
            $rel = strtolower($anchor->getAttribute('rel') ?? '');
            
            if (empty($href) || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, '#')) continue;
            
            $normalized = $this->normalizeUrl($href, $baseScheme, $baseHost, $basePath);
            
            if ($normalized && $this->isSameDomain($normalized, $domain)) {
                $isNofollow = str_contains($rel, 'nofollow');
                // Key by URL to deduplicate per page
                $links[$normalized] = [
                    'url' => $normalized,
                    'is_nofollow' => $isNofollow
                ];
            }
        }
        libxml_clear_errors();
        return array_values($links);
    }

    private function persistLinks(Page $fromPage, array $links): void
    {
        foreach ($links as $link) {
            // Upsert Logic manually or via upsert()
            // Using DB::table for performance or Model
            \App\Models\Seo\PageLink::upsert(
                [
                    'site_id' => $fromPage->site_id,
                    'from_page_id' => $fromPage->id,
                    'to_url' => $link['url'],
                    'to_url_hash' => hash('sha256', $link['url']), // Handle Key Length
                    'is_internal' => true,
                    'is_nofollow' => $link['is_nofollow'],
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                ],
                ['from_page_id', 'to_url_hash'], // Unique Key
                ['last_seen_at'] // Update columns
            );
        }
    }

    /**
     * Post-Crawl: Resolve all unlinked edges to Page IDs.
     */
    private function resolveLinks(int $siteId): void
    {
        // SQL update efficiently
        // UPDATE page_links pl
        // JOIN pages p ON p.site_id = pl.site_id AND p.url = pl.to_url
        // SET pl.to_page_id = p.id
        // WHERE pl.site_id = ? AND pl.to_page_id IS NULL
        
        \Illuminate\Support\Facades\DB::update("
            UPDATE page_links pl
            JOIN pages p ON p.site_id = pl.site_id AND p.url = pl.to_url
            SET pl.to_page_id = p.id
            WHERE pl.site_id = ? AND pl.to_page_id IS NULL
        ", [$siteId]);
    }

    private function normalizeUrl(string $href, string $baseScheme, string $baseHost, string $basePath): ?string
    {
        if (preg_match('/^https?:\/\//', $href)) return $this->cleanUrl($href);
        if (str_starts_with($href, '//')) return $this->cleanUrl($baseScheme . ':' . $href);
        if (str_starts_with($href, '/')) return $this->cleanUrl($baseScheme . '://' . $baseHost . $href);
        $baseDir = rtrim(dirname($basePath), '/');
        // Handle relative paths ./ and ../
        // Simple append for now as strict relative resolution is complex, but standard browsers handle /foo/bar + baz = /foo/baz
        if ($basePath === '/') return $this->cleanUrl($baseScheme . '://' . $baseHost . '/' . $href);
        return $this->cleanUrl($baseScheme . '://' . $baseHost . $baseDir . '/' . $href);
    }

    private function cleanUrl(string $url): string
    {
        $url = preg_replace('/#.*$/', '', $url);
        $url = rtrim($url, '/'); // Normalize trailing slashes to avoid duplicates
        // Basic re-parse to clean
        $parsed = parse_url($url);
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
