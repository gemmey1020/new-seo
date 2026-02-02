<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Site\Site;
use App\Models\Crawl\SitemapSource;
use App\Models\Seo\Page;

class ImportSitemapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $siteId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $siteId)
    {
        $this->siteId = $siteId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $site = Site::findOrFail($this->siteId);
        
        // Fetch sitemaps for the site
        $sources = SitemapSource::where('site_id', $site->id)->get();

        foreach ($sources as $source) {
            try {
                $response = Http::timeout(30)->get($source->sitemap_url);
                
                if ($response->failed()) {
                    $source->update([
                        'status' => 'error',
                        'last_error' => 'HTTP ' . $response->status(),
                        'last_fetched_at' => now(),
                    ]);
                    continue;
                }

                $xml = simplexml_load_string($response->body());
                
                if ($xml === false) {
                    $source->update([
                        'status' => 'error',
                        'last_error' => 'Invalid XML',
                        'last_fetched_at' => now(),
                    ]);
                    continue;
                }

                $count = 0;
                // Basic Sitemap Parsing (urlset > url > loc)
                foreach ($xml->url as $url) {
                    $loc = (string) $url->loc;
                    $path = parse_url($loc, PHP_URL_PATH) ?? '/';
                    
                    Page::firstOrCreate(
                        ['site_id' => $site->id, 'url' => $loc],
                        [
                            'path' => $path,
                            'first_seen_at' => now(),
                            'index_status' => 'unknown', // Default
                        ]
                    );
                    $count++;
                }

                $source->update([
                    'status' => 'active',
                    'last_error' => null,
                    'last_fetched_at' => now(),
                ]);

                Log::info("Sitemap imported for site {$site->id}: {$count} pages.");

            } catch (\Exception $e) {
                $source->update([
                    'status' => 'error',
                    'last_error' => $e->getMessage(),
                    'last_fetched_at' => now(),
                ]);
            }
        }
    }
}
