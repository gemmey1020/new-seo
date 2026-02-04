<?php

namespace App\Services;

use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use App\Models\Seo\SeoMetaVersion;
use App\Models\Auth\User;
use App\Services\AuthorityService;
use App\Enums\ActionClass;
use Illuminate\Support\Facades\DB;

class MetaService
{
    protected $authority;

    public function __construct(AuthorityService $authority)
    {
        $this->authority = $authority;
    }

    /**
     * Update Page SEO Meta (Class B - Human Gated).
     */
    public function updateMeta(Page $page, array $data, User $actor): SeoMeta
    {
        $site = $page->site;
        
        // 1. Validate Sanctuary Rule (Homepage)
        if ($page->path === '/' || $page->path === '') {
             // For Homepage, we BLOCK dangerous toggles.
             // We check if data implies Noindex or Redirect (via meta refresh? No, only fields).
             // "Homepage can NEVER be: noindex, redirected, canonicalized away"
             
             if (isset($data['index_status']) && $data['index_status'] === 'noindex') {
                 throw new \Exception("SANCTUARY VIOLATION: Homepage cannot be noindex.");
             }
             if (!empty($data['canonical_override'])) {
                 throw new \Exception("SANCTUARY VIOLATION: Homepage cannot have canonical override.");
             }
        }

        // 2. Authority Check
        // STRICT BOOLEAN CHECK
        // AuthorityService internal logic determines Allow/Deny and logs it.
        // We just obey.
        $allowed = $this->authority->canPerform($site, ActionClass::CLASS_B, 'update_meta', $data, $actor);
        
        if (!$allowed) {
            throw new \Exception("Meta Update DENIED by Authority.");
        }

        return DB::transaction(function () use ($page, $data, $actor) {
            $meta = SeoMeta::firstOrCreate(['page_id' => $page->id]);

            // 3. Snapshot Version (Undo Logic)
            $this->createVersion($meta, $actor, "Update by user");

            // 4. Update Live Data
            $meta->fill($data);
            $meta->save();

            return $meta;
        });
    }

    /**
     * Undo a change by restoring a version.
     */
    public function undoChange(int $versionId, User $actor): SeoMeta
    {
        $version = SeoMetaVersion::findOrFail($versionId);
        $meta = $version->meta;
        $page = $meta->page;
        $site = $page->site;

        // Authority Check (Undo is also a write)
        $allowed = $this->authority->canPerform($site, ActionClass::CLASS_B, 'undo_meta', ['version_id' => $versionId], $actor);
        if (!$allowed) {
            throw new \Exception("Meta Undo DENIED by Authority.");
        }

        return DB::transaction(function () use ($meta, $version, $actor) {
            // Snapshot current state before reverting (Redo logic?)
            $this->createVersion($meta, $actor, "Undo to version #{$version->id}");

            // Restore fields
            $meta->update([
                'title' => $version->title,
                'description' => $version->description,
                'robots' => $version->robots,
                'index_status' => $version->index_status,
                'canonical_override' => $version->canonical_override,
                'og_title' => $version->og_title,
                // ... map all fields strictly
            ]);
            
            return $meta;
        });
    }

    private function createVersion(SeoMeta $meta, User $actor, string $note)
    {
        SeoMetaVersion::create([
            'seo_meta_id' => $meta->id,
            'user_id' => $actor->id,
            'title' => $meta->title,
            'description' => $meta->description,
            'robots' => $meta->robots,
            'index_status' => $meta->index_status,
            'canonical_override' => $meta->canonical_override,
            'og_title' => $meta->og_title,
            'og_description' => $meta->og_description,
            'og_image_url' => $meta->og_image_url,
            'twitter_card' => $meta->twitter_card,
            'twitter_title' => $meta->twitter_title,
            'twitter_description' => $meta->twitter_description,
            'twitter_image_url' => $meta->twitter_image_url,
            'change_note' => $note,
            'created_at' => now(), // Manually set if timestamps disabled
        ]);
    }
}
