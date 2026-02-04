<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Crawl\CrawlRun;
use App\Models\Crawl\CrawlLog;

class CrawlController extends Controller
{
    /**
     * List Runs.
     */
    public function index(Request $request, $siteId)
    {
        return CrawlRun::where('site_id', $siteId)->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Start/Store Run.
     */
    public function store(Request $request, $siteId)
    {
        return CrawlRun::create([
            'site_id' => $siteId,
            'mode' => 'sitemap', // MVP default as per spec
            'status' => 'pending',
            'started_at' => now(),
        ]);
    }

    /**
     * Get Logs.
     */
    public function logs(Request $request, $siteId)
    {
        return CrawlLog::where('site_id', $siteId)->orderBy('crawled_at', 'desc')->paginate(50);
    }
}
