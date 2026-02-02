<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Crawl\InternalLink;
use App\Models\Seo\Page;

class LinkController extends Controller
{
    /**
     * Get Link Graph.
     */
    public function index(Request $request, $siteId)
    {
        return InternalLink::where('site_id', $siteId)->paginate(100);
    }

    /**
     * Rebuild Links (Trigger).
     */
    public function rebuild(Request $request, $siteId)
    {
        return response()->json(['message' => 'Link rebuild queued (placeholder)'], 202);
    }

    /**
     * Get Orphans.
     */
    public function orphans(Request $request, $siteId)
    {
        // Pages with NO inbound links (except homepage)
        return Page::where('site_id', $siteId)
            ->where('path', '!=', '/')
            ->doesntHave('inboundLinks')
            ->paginate(50);
    }
}
