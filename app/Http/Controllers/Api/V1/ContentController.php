<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Services\ContentService;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    /**
     * Get On-Demand Content Analysis for a Page.
     * 
     * @param Site $site
     * @param Page $page
     * @param ContentService $service
     * @return JsonResponse
     */
    public function show(Site $site, Page $page, ContentService $service): JsonResponse
    {
        // Enforce Relationship
        if ($page->site_id !== $site->id) {
            abort(404, 'Page not found for this site.');
        }

        $analysis = $service->analyze($page);

        return response()->json([
            'data' => $analysis
        ]);
    }
}
