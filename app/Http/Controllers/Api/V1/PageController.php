<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Seo\Page;
use App\Models\Site\Site;

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
