<?php

namespace App\Services;

use App\Models\Site\Site;
use App\Models\Crawl\CrawlRun;
use App\Jobs\CrawlRunJob;
use Illuminate\Support\Facades\Log;

/**
 * SiteBootstrapService
 * 
 * ENGINE CORE COMPLIANT.
 * 
 * Bootstrap ONLY queues an initial CrawlRun.
 * Pages and Meta are created ONLY by CrawlRunJob execution.
 * 
 * INVARIANT: No synthetic pages. All pages come from crawl discovery.
 */
class SiteBootstrapService
{
    /**
     * Bootstrap a newly created site.
     * 
     * This ONLY queues an initial crawl run.
     * The crawl job will discover and create pages.
     *
     * @param Site $site The newly created site
     * @return array Bootstrap results summary
     */
    public function bootstrap(Site $site): array
    {
        $results = [
            'site_id' => $site->id,
            'crawl_queued' => false,
        ];

        try {
            // Queue initial Crawl Run (status: pending)
            $crawlRun = $this->queueInitialCrawl($site);
            $results['crawl_queued'] = true;
            $results['crawl_run_id'] = $crawlRun->id;

            Log::info('SiteBootstrapService: Initial crawl queued', $results);

        } catch (\Exception $e) {
            Log::error('SiteBootstrapService: Bootstrap failed', [
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Queue an initial crawl run and dispatch the job.
     */
    protected function queueInitialCrawl(Site $site): CrawlRun
    {
        $crawlRun = CrawlRun::create([
            'site_id' => $site->id,
            'mode' => 'full',
            'user_agent' => 'SEO-OS-Bot/1.0',
            'status' => 'pending',
            'pages_discovered' => 0,
            'pages_crawled' => 0,
            'errors_count' => 0,
        ]);

        // Dispatch the crawl job to the queue
        CrawlRunJob::dispatch($crawlRun->id);

        return $crawlRun;
    }
}

