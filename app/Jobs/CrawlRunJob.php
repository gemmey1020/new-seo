<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Crawl\CrawlRun;
use App\Models\Crawl\CrawlLog;
use App\Models\Seo\Page;

class CrawlRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $crawlRunId;

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
        $run = CrawlRun::findOrFail($this->crawlRunId);
        $run->update(['status' => 'running', 'started_at' => now()]);

        // Fetch pages to crawl (simple approach: all pages for this site)
        $pages = Page::where('site_id', $run->site_id)->get();
        
        $crawledCount = 0;
        $errorCount = 0;

        foreach ($pages as $page) {
            try {
                $start = microtime(true);
                $response = Http::withUserAgent($run->user_agent)
                    ->timeout(10)
                    ->get($page->url);
                $duration = round((microtime(true) - $start) * 1000);

                // Log result
                CrawlLog::create([
                    'site_id' => $run->site_id,
                    'page_id' => $page->id,
                    'crawl_run_id' => $run->id,
                    'status_code' => $response->status(),
                    'response_ms' => $duration,
                    'bytes' => strlen($response->body()),
                    'content_type' => $response->header('Content-Type'),
                    'crawled_at' => now(),
                ]);

                // Update Page
                $page->update([
                    'http_status_last' => $response->status(),
                    'last_crawled_at' => now(),
                ]);

                // Cache Body for Links/Audit Jobs (TTL 1 hour)
                if ($response->successful()) {
                    Cache::put("crawl_body_{$run->id}_{$page->id}", $response->body(), 3600);
                }

                $crawledCount++;

            } catch (\Exception $e) {
                $errorCount++;
                CrawlLog::create([
                    'site_id' => $run->site_id,
                    'page_id' => $page->id,
                    'crawl_run_id' => $run->id,
                    'status_code' => 0, // Exception
                    'notes' => $e->getMessage(),
                    'crawled_at' => now(),
                ]);
            }
        }

        $run->update([
            'status' => 'completed',
            'pages_crawled' => $crawledCount,
            'errors_count' => $errorCount,
            'finished_at' => now(),
        ]);
        
        // Trigger follow-up jobs?
        // Spec 14 says "InternalLinksRebuildJob(site_id, crawl_run_id)".
        // "DO NOT enqueue sub-jobs" in forbidden actions (Phase 5).
        // User instruction 7: "DO NOT enqueue sub-jobs".
        // Okay, so valid orchestration relies on Scheduler or chained dispatch from Controller.
        // I will adhere to "DO NOT enqueue sub-jobs".
    }
}
