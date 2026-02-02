<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use App\Models\Seo\SeoMetaVersion;

class MetaController extends Controller
{
    /**
     * Get Meta for a Page.
     */
    public function show($siteId, $pageId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        return $page->meta ?? [];
    }

    /**
     * Update Meta for a Page (and create version).
     */
    public function update(Request $request, $siteId, $pageId)
    {
        // Update or Create Meta
        $meta = SeoMeta::updateOrCreate(
            ['page_id' => $pageId],
            $request->all()
        );

        // Create Version History
        $versionData = $meta->only([
            'title', 'description', 'robots', 
            'og_title', 'og_description', 'og_image_url', 
            'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image_url'
        ]);
        
        $versionData['seo_meta_id'] = $meta->id;
        $versionData['user_id'] = auth()->id();
        $versionData['change_note'] = $request->input('change_note');
        
        SeoMetaVersion::create($versionData);

        return $meta;
    }

    /**
     * Get Version History.
     */
    public function versions($siteId, $pageId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        $meta = SeoMeta::where('page_id', $page->id)->firstOrFail();
        
        return $meta->versions()->orderBy('created_at', 'desc')->get();
    }
}
