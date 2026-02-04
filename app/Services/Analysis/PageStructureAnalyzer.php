<?php

namespace App\Services\Analysis;

use App\Models\Seo\Page;
use App\Models\Seo\PageLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PageStructureAnalyzer
{
    /**
     * Analyze the structure of a specific page.
     * 
     * @param Page $page
     * @return array
     */
    public function analyze(Page $page): array
    {
        $siteId = $page->site_id;
        
        // 1. Inbound/Outbound Counts (Real-time DB query is fast enough for single page, or could be cached)
        // Using relationships might be slower if not eager loaded, direct count is better for analysis
        $inboundCount = $page->inboundLinks()->count();
        $outboundCount = $page->outboundLinks()->count();

        // 2. Depth from Home (BFS)
        $depthMap = $this->getSiteDepthMap($siteId);
        $depth = $depthMap[$page->id] ?? null;

        // 3. Orphan Status
        // A page is an orphan if it has 0 inbound links and is NOT the homepage.
        // Note: Homepage (path /) usually has 0 inbound internal links initially, but shouldn't be flagged as orphan structure-wise.
        $isHome = $page->path === '/';
        $isOrphan = !$isHome && $inboundCount === 0;

        // 4. Internal Authority Score (v1 Heuristic)
        // Simple normalized score based on log(inbound_count)
        // This is a placeholder for true PageRank
        $authorityScore = 0;
        if ($inboundCount > 0) {
            $authorityScore = min(100, round(log($inboundCount + 1) * 20)); 
        }

        return [
            'inbound_count' => $inboundCount,
            'outbound_count' => $outboundCount,
            'is_orphan' => $isOrphan,
            'depth_from_home' => $depth,
            'internal_authority_score' => $authorityScore,
        ];
    }

    /**
     * Get (and cache) the BFS depth map for the entire site.
     * Returns matching: [page_id => depth, ...]
     */
    public function getSiteDepthMap(int $siteId): array
    {
        // Cache Key Logic: valid for 5 mins
        $cacheKey = "site:{$siteId}:structure_depth";
        
        return Cache::remember($cacheKey, 300, function () use ($siteId) {
            return $this->computeBfsDepth($siteId);
        });
    }

    /**
     * Perform BFS traversal to calculate depth from homepage.
     */
    private function computeBfsDepth(int $siteId): array
    {
        // 1. Find Homepage ID
        $homePage = DB::table('pages')
            ->where('site_id', $siteId)
            ->where('path', '/')
            ->select('id')
            ->first();

        if (!$homePage) {
            return []; // No homepage, no depth structure
        }

        // 2. Build Adjacency List (Internal Links Only, Resolved Pages Only)
        // Memory efficient selection
        $edges = DB::table('page_links')
            ->where('site_id', $siteId)
            ->where('is_internal', true)
            ->whereNotNull('to_page_id')
            ->select('from_page_id', 'to_page_id')
            ->get();

        $adj = [];
        foreach ($edges as $edge) {
            $adj[$edge->from_page_id][] = $edge->to_page_id;
        }

        // 3. BFS Execution
        $depths = [];
        $queue = [];

        // Init
        $rootId = $homePage->id;
        $depths[$rootId] = 0;
        $queue[] = $rootId;

        $pointer = 0;
        while ($pointer < count($queue)) {
            $currentId = $queue[$pointer];
            $pointer++;
            $currentDepth = $depths[$currentId];

            if (isset($adj[$currentId])) {
                foreach ($adj[$currentId] as $neighborId) {
                    if (!isset($depths[$neighborId])) {
                        $depths[$neighborId] = $currentDepth + 1;
                        $queue[] = $neighborId;
                    }
                }
            }
        }

        return $depths;
    }
}
