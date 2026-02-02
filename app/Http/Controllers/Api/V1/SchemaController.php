<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Seo\Page;
use App\Models\Seo\Schema;
use App\Models\Seo\SchemaVersion;

class SchemaController extends Controller
{
    /**
     * List Schemas for a Page.
     */
    public function index(Request $request, $siteId, $pageId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        return $page->schemas;
    }

    /**
     * Create Schema.
     */
    public function store(Request $request, $siteId, $pageId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        
        $data = $request->all();
        $data['page_id'] = $page->id;
        
        return Schema::create($data);
    }

    /**
     * Update Schema (and create version).
     */
    public function update(Request $request, $siteId, $pageId, $schemaId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        $schema = Schema::where('page_id', $page->id)->findOrFail($schemaId);
        
        $schema->update($request->all());

        // Create Version
        SchemaVersion::create([
            'schema_id' => $schema->id,
            'user_id' => auth()->id(),
            'json_ld' => $schema->json_ld,
            'change_note' => $request->input('change_note')
        ]);

        return $schema;
    }

    /**
     * Validate Schema (Placeholder).
     */
    public function validate(Request $request, $siteId, $pageId, $schemaId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        $schema = Schema::where('page_id', $page->id)->findOrFail($schemaId);
        
        // Validation Logic Placeholder
        $schema->update([
            'is_validated' => true, 
            'last_validated_at' => now()
        ]);

        return $schema;
    }

    /**
     * Get Versions.
     */
    public function versions($siteId, $pageId, $schemaId)
    {
        $page = Page::where('site_id', $siteId)->findOrFail($pageId);
        $schema = Schema::where('page_id', $page->id)->findOrFail($schemaId);
        
        return $schema->versions()->orderBy('created_at', 'desc')->get();
    }
}
