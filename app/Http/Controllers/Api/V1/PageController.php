<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Seo\Page;
use App\Models\Site\Site;
use App\Services\Policy\PolicyEvaluator;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $siteId)
    {
        $query = Page::where('site_id', $siteId)->orderBy('id', 'asc');
        
        // Filter: Orphans
        if ($request->has('orphan') && $request->boolean('orphan')) {
            $query->doesntHave('inboundLinks')->where('path', '!=', '/');
        }

        $pages = $query->paginate(50);
        
        // Append Computed Attributes for UI
        $pages->getCollection()->each->setAppends(['analysis', 'structure']);

        // Policy Decoration (Passive Visibility)
        if ($request->input('include_policy', 1)) {
            $evaluator = new PolicyEvaluator();
            $pages->getCollection()->transform(function ($page) use ($evaluator) {
                $policy = $evaluator->evaluate($page);
                $page->setAttribute('policy_summary', $policy['policy_summary']);
                $page->setAttribute('violations_count', $policy['policy_summary']['violations_count'] ?? count($policy['violations'] ?? []));
                $page->setAttribute('violations_preview', array_slice($policy['violations'] ?? [], 0, 3));
                return $page;
            });
        }
        
        return $pages;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $siteId)
    {
        $data = $request->all();
        $data['site_id'] = $siteId;
        return Page::create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show($siteId, $pageId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        $page->setAppends(['analysis', 'structure']);

        // Policy Decoration (Passive Visibility - Full Inspector)
        $evaluator = new PolicyEvaluator();
        $policy = $evaluator->evaluate($page);
        $page->setAttribute('policy_summary', $policy['policy_summary']);
        $page->setAttribute('violations', $policy['violations']);
        $page->setAttribute('violations_count', $policy['policy_summary']['violations_count'] ?? count($policy['violations']));

        return $page;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $siteId, $pageId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        $page->update($request->all());
        return $page;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($siteId, $pageId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        $page->delete();
        return response()->noContent();
    }

    /**
     * Import pages from sitemap.
     */
    public function importSitemap(Request $request, $siteId)
    {
        // Placeholder for Job dispatch
        return response()->json(['message' => 'Sitemap import queued (placeholder)'], 202);
    }
}
