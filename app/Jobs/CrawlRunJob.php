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
use DOMDocument;
use DOMXPath;

/**
 * CrawlRunJob - ENGINE CORE EXECUTION
 * 
 * This job performs REAL crawl execution:
 * 1. Transitions CrawlRun through state machine
 * 2. Discovers URLs starting from seed (homepage)
 * 3. Performs actual HTTP GET requests
 * 4. Parses links from HTML
 * 5. Creates Page records ONLY through discovery
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

        // Must be pending to start
        if ($run->status !== CrawlRun::STATUS_PENDING) {
            Log::warning('CrawlRunJob: CrawlRun not in pending state', [
                'id' => $run->id,
                'status' => $run->status
            ]);
            return;
        }

        try {
            // Transition to running
            $run->transitionTo(CrawlRun::STATUS_RUNNING);

            $site = $run->site;
            if (!$site) {
                $run->fail('Site not found');
                return;
            }

            // Build seed URL
            $seedUrl = 'https://' . $site->domain . '/';
            
            // Initialize crawl state
            $urlQueue = [['url' => $seedUrl, 'depth' => 0]];
            $visited = [];
            $pagesDiscovered = 0;
            $pagesCrawled = 0;
            $errorsCount = 0;

            while (!empty($urlQueue) && $pagesCrawled < self::MAX_PAGES) {
                $item = array_shift($urlQueue);
                $url = $item['url'];
                $depth = $item['depth'];

                // Skip if already visited
                if (isset($visited[$url])) {
                    continue;
                }
                $visited[$url] = true;

                // Rate limiting
                usleep(self::REQUEST_DELAY_MS * 1000);

                // Perform HTTP GET
                $result = $this->fetchUrl($url, $run);
                $pagesCrawled++;

                if ($result['success']) {
                    // Create or update Page record
                    $page = $this->upsertPage($site->id, $url, $result, $run->id, $depth);
                    $pagesDiscovered++;

                    // Log the request
                    $this->logRequest($run, $page, $url, $result);

                    // Parse and queue links if within depth limit
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
                    
                    // Log the error
                    $this->logError($run, $url, $result);
                }

                // Update progress periodically
                if ($pagesCrawled % 10 === 0) {
                    $run->update([
                        'pages_discovered' => $pagesDiscovered,
                        'pages_crawled' => $pagesCrawled,
                        'errors_count' => $errorsCount,
                    ]);
                }
            }

            // Final update and transition to completed
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
                'errors_count' => $errorsCount,
            ]);

        } catch (\Exception $e) {
            Log::error('CrawlRunJob: Failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
            
            // Transition to failed
            $run->error_message = $e->getMessage();
            if ($run->status === CrawlRun::STATUS_RUNNING) {
                $run->transitionTo(CrawlRun::STATUS_FAILED);
            }
        }
    }

    /**
     * Fetch a URL and return response data.
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
            $finalUrl = $response->effectiveUri()?->__toString() ?? $url;

            return [
                'success' => true,
                'status_code' => $response->status(),
                'response_ms' => $duration,
                'bytes' => strlen($response->body()),
                'content_type' => $this->parseContentType($response->header('Content-Type')),
                'final_url' => $finalUrl,
                'body' => $response->body(),
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
     * Parse Content-Type header to get base type.
     */
    private function parseContentType(?string $header): string
    {
        if (!$header) {
            return 'unknown';
        }
        $parts = explode(';', $header);
        return trim($parts[0]);
    }

    /**
     * Create or update a Page record.
     * 
     * INVARIANT: This is the ONLY place where Pages are created.
     */
    private function upsertPage(int $siteId, string $url, array $result, int $crawlRunId, int $depth): Page
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        
        $page = Page::where('site_id', $siteId)->where('url', $url)->first();
        
        if ($page) {
            // Update existing page
            $page->update([
                'http_status_last' => $result['status_code'],
                'last_crawled_at' => now(),
            ]);
        } else {
            // Create new page - this will validate the invariant
            $page = Page::create([
                'site_id' => $siteId,
                'url' => $url,
                'path' => $path,
                'page_type' => $depth === 0 ? 'homepage' : 'general',
                'index_status' => 'unknown',
                'http_status_last' => $result['status_code'],
                'depth_level' => $depth,
                'first_seen_at' => now(),
                'last_crawled_at' => now(),
                'discovered_by_crawl_run_id' => $crawlRunId, // REQUIRED by invariant
            ]);
        }
        
        return $page;
    }

    /**
     * Log a successful request.
     */
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

    /**
     * Log an error response.
     */
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
     * Parse links from HTML content.
     * 
     * Returns only same-domain, normalized URLs.
     */
    private function parseLinks(string $html, string $baseUrl, string $domain): array
    {
        $links = [];
        
        if (empty($html)) {
            return $links;
        }

        // Suppress HTML parsing errors
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        
        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query('//a[@href]');

        $baseParts = parse_url($baseUrl);
        $baseScheme = $baseParts['scheme'] ?? 'https';
        $baseHost = $baseParts['host'] ?? $domain;
        $basePath = $baseParts['path'] ?? '/';

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            
            // Skip empty, javascript, mailto, tel, anchors
            if (empty($href) || 
                str_starts_with($href, 'javascript:') || 
                str_starts_with($href, 'mailto:') ||
                str_starts_with($href, 'tel:') ||
                str_starts_with($href, '#')) {
                continue;
            }

            // Normalize URL
            $normalized = $this->normalizeUrl($href, $baseScheme, $baseHost, $basePath);
            
            if ($normalized && $this->isSameDomain($normalized, $domain)) {
                $links[$normalized] = true; // Use as key for deduplication
            }
        }

        libxml_clear_errors();
        
        return array_keys($links);
    }

    /**
     * Normalize a URL to absolute form.
     */
    private function normalizeUrl(string $href, string $baseScheme, string $baseHost, string $basePath): ?string
    {
        // Already absolute
        if (preg_match('/^https?:\/\//', $href)) {
            return $this->cleanUrl($href);
        }

        // Protocol-relative
        if (str_starts_with($href, '//')) {
            return $this->cleanUrl($baseScheme . ':' . $href);
        }

        // Root-relative
        if (str_starts_with($href, '/')) {
            return $this->cleanUrl($baseScheme . '://' . $baseHost . $href);
        }

        // Relative
        $baseDir = rtrim(dirname($basePath), '/');
        return $this->cleanUrl($baseScheme . '://' . $baseHost . $baseDir . '/' . $href);
    }

    /**
     * Clean URL by removing fragments and normalizing.
     */
    private function cleanUrl(string $url): string
    {
        // Remove fragment
        $url = preg_replace('/#.*$/', '', $url);
        
        // Remove trailing slash from non-root paths
        $parsed = parse_url($url);
        if (isset($parsed['path']) && $parsed['path'] !== '/' && str_ends_with($parsed['path'], '/')) {
            $parsed['path'] = rtrim($parsed['path'], '/');
        }

        // Rebuild URL
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        return $scheme . '://' . $host . $path . $query;
    }

    /**
     * Check if URL is on the same domain.
     */
    private function isSameDomain(string $url, string $domain): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        
        // Exact match or www subdomain match
        return $host === $domain || 
               $host === 'www.' . $domain ||
               $domain === 'www.' . $host;
    }
}
