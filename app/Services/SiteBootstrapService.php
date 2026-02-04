<?php

namespace App\Services;

use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use App\Models\Crawl\CrawlRun;
use Illuminate\Support\Facades\Log;

/**
 * SiteBootstrapService (B.1)
 * 
 * Initializes a newly created site with baseline data:
 * - Homepage Page record (slug: /)
 * - Meta record for homepage
 * - Queued initial CrawlRun
 * 
 * This resolves Section 0.1 Bootstrap failure where sites are created
 * without any initial data for Pages/Meta/Audit.
 */
class SiteBootstrapService
{
    /**
     * Bootstrap a newly created site with initial data.
     *
     * @param Site $site The newly created site
     * @return array Bootstrap results summary
     */
    public function bootstrap(Site $site): array
    {
        $results = [
            'site_id' => $site->id,
            'homepage_created' => false,
            'meta_created' => false,
            'crawl_queued' => false,
        ];

        try {
            // 1. Create homepage Page record
            $homepage = $this->createHomepage($site);
            $results['homepage_created'] = true;
            $results['homepage_id'] = $homepage->id;

            // 2. Create initial Meta record for homepage
            $meta = $this->createHomepageMeta($homepage, $site);
            $results['meta_created'] = true;
            $results['meta_id'] = $meta->id;

            // 3. Queue initial Crawl Run (status: pending)
            $crawlRun = $this->queueInitialCrawl($site);
            $results['crawl_queued'] = true;
            $results['crawl_run_id'] = $crawlRun->id;

            Log::info('SiteBootstrapService: Site bootstrapped successfully', $results);

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
     * Create the homepage Page record.
     */
    protected function createHomepage(Site $site): Page
    {
        return Page::create([
            'site_id' => $site->id,
            'url' => 'https://' . $site->domain . '/',
            'path' => '/',
            'page_type' => 'homepage',
            'index_status' => 'unknown',
            'http_status_last' => null,
            'depth_level' => 0,
            'first_seen_at' => now(),
        ]);
    }

    /**
     * Create initial Meta record for the homepage.
     */
    protected function createHomepageMeta(Page $homepage, Site $site): SeoMeta
    {
        return SeoMeta::create([
            'page_id' => $homepage->id,
            'title' => $site->name . ' - Homepage',
            'description' => 'Homepage of ' . $site->domain,
            'robots' => 'index,follow',
        ]);
    }

    /**
     * Queue an initial crawl run (pending status).
     */
    protected function queueInitialCrawl(Site $site): CrawlRun
    {
        return CrawlRun::create([
            'site_id' => $site->id,
            'mode' => 'full',
            'user_agent' => 'SEO-OS-Bot/1.0',
            'status' => 'pending',
            'pages_discovered' => 0,
            'pages_crawled' => 0,
            'errors_count' => 0,
            'started_at' => now(),
        ]);
    }
}
