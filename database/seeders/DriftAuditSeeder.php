<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Models\Crawl\CrawlRun;
use App\Models\Crawl\CrawlLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DriftAuditSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Drift Audit Data (25+ Sites)...');

        // Scenarios
        $scenarios = [
            'Ghost' => 5, // Persistent Ghost
            'State' => 5, // Persistent State
            'Transient' => 5, 
            'Clean' => 5,
            'Mixed' => 5
        ];

        $counter = 1;

        foreach ($scenarios as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createSiteScenario($type, $counter++);
            }
        }
    }

    private function createSiteScenario(string $type, int $id)
    {
        $domain = "audit-test-{$type}-{$id}.com";
        $site = Site::create([
            'domain' => $domain,
            'name' => "Audit {$type} {$id}",
            'is_active' => true
        ]);

        $this->command->info("Created Site: {$domain} ({$type})");

        // Create 3 Runs for History
        // Timestamps: -3 days, -2 days, Today
        $dates = [
            Carbon::now()->subDays(3),
            Carbon::now()->subDays(2),
            Carbon::now()
        ];

        foreach ($dates as $index => $date) {
            $isLastRun = ($index === 2);
            
            // Determine if THIS run should be "Bad" based on Type
            $isBad = false;
            
            if ($type === 'Ghost' || $type === 'State') {
                // Persistent means ALL 3 runs are bad (or at least last 3)
                $isBad = true; 
            } elseif ($type === 'Transient') {
                // Only the LAST run is bad
                $isBad = $isLastRun;
            } elseif ($type === 'Mixed') {
                // Random
                $isBad = (rand(0, 1) === 1);
            }

            $run = CrawlRun::create([
                'site_id' => $site->id,
                'mode' => 'full',
                'status' => 'completed',
                'started_at' => $date,
                'finished_at' => $date->copy()->addMinutes(10),
                'pages_crawled' => 20,
            ]);

            // Create Logs
            // If Bad: Inject Drift
            // If Good: All 200 OK
            
            for ($p = 1; $p <= 20; $p++) {
                $status = 200;
                
                if ($isBad) {
                    if ($type === 'Ghost') {
                        // > 10% 404s. 20 pages. 3 pages = 15%.
                        if ($p <= 3) $status = 404;
                    } elseif ($type === 'State') {
                        // > 1% non-200. 20 pages. 1 page = 5%.
                        if ($p <= 2) $status = 500;
                    } elseif ($type === 'Transient' || $type === 'Mixed') {
                        // Random drift type for these
                        if ($p <= 3) $status = 500;
                    }
                }

                // Create Page (if not exists) - Simplification: Create Page then Log
                $page = Page::firstOrCreate(
                    ['site_id' => $site->id, 'path' => "/page-{$p}"],
                    ['url' => "https://{$domain}/page-{$p}"]
                );
                
                // Update Page Status to match latest run
                if ($isLastRun) {
                    $page->http_status_last = $status;
                    $page->save();
                }

                CrawlLog::create([
                    'site_id' => $site->id,
                    'crawl_run_id' => $run->id,
                    'page_id' => $page->id,
                    'status_code' => $status,
                    'response_ms' => 150,
                    'final_url' => $page->url
                ]);
            }
        }
    }
}
