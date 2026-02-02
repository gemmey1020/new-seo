<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Crawl\CrawlRun;
use App\Models\Crawl\InternalLink;
use App\Models\Seo\Page;

class InternalLinksRebuildJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $siteId;
    public $crawlRunId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $siteId, int $crawlRunId)
    {
        $this->siteId = $siteId;
        $this->crawlRunId = $crawlRunId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Iterate pages involved in this run
        $pages = Page::where('site_id', $this->siteId)->get();

        foreach ($pages as $page) {
            $html = Cache::get("crawl_body_{$this->crawlRunId}_{$page->id}");
            
            if (!$html) {
                continue; // Cannot process without body
            }

            // Suppress DOM warnings
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML($html);
            libxml_clear_errors();

            $links = $dom->getElementsByTagName('a');

            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                $rel = $link->getAttribute('rel');
                $text = $link->nodeValue;
                
                // Normalization Logic (MVP: Simplified)
                // If starts with /, append domain? 
                // We stored Pages by full URL or partial?
                // Spec 5 implies "url" is unique per site. ImportSitemapJob stored full "loc".
                
                // Convert relative to absolute
                // Assuming $page->url is absolute base
                // MVP: Only handle exact matches found in Page table for now to map ID
                // Or basic logic:
                if (str_starts_with($href, '/')) {
                     // Get domain from page->url
                     $parts = parse_url($page->url);
                     $base = $parts['scheme'] . '://' . $parts['host'];
                     $targetUrl = $base . $href;
                } else {
                     $targetUrl = $href;
                }

                $targetPage = Page::where('site_id', $this->siteId)->where('url', $targetUrl)->first();

                if ($targetPage) {
                    InternalLink::updateOrCreate(
                        [
                            'site_id' => $this->siteId,
                            'from_page_id' => $page->id,
                            'to_page_id' => $targetPage->id,
                        ],
                        [
                            'anchor_text' => substr(trim($text), 0, 255),
                            'rel_attr' => substr($rel, 0, 255),
                            'last_seen_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
