<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Models\Audit\AuditRule;
use App\Models\Audit\SeoAudit;

class AuditRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $siteId;

    public function __construct(int $siteId)
    {
        $this->siteId = $siteId;
    }

    public function handle(): void
    {
        $site = Site::findOrFail($this->siteId);
        $pages = Page::where('site_id', $this->siteId)->with('meta')->get();
        $rules = AuditRule::where('is_active', true)->get();

        foreach ($pages as $page) {
            foreach ($rules as $rule) {
                $violation = $this->checkRule($rule, $page);
                
                if ($violation) {
                    SeoAudit::firstOrCreate(
                        [
                            'site_id' => $site->id,
                            'page_id' => $page->id,
                            'rule_id' => $rule->id,
                            'status' => 'open',
                        ],
                        [
                            'severity' => $rule->severity_default,
                            'description' => $violation['description'],
                            'evidence_json' => $violation['evidence'],
                            'detected_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    protected function checkRule(AuditRule $rule, Page $page)
    {
        $meta = $page->meta;
        
        // 1. missing_title
        if ($rule->key === 'missing_title') {
            if (!$meta || empty($meta->title)) {
                return ['description' => 'Title tag is missing or empty.', 'evidence' => []];
            }
        }

        // 2. title_too_short
        if ($rule->key === 'title_too_short') {
            if ($meta && strlen($meta->title) < 10) {
                 return ['description' => 'Title is too short.', 'evidence' => ['length' => strlen($meta->title)]];
            }
        }

        // 3. title_too_long
        if ($rule->key === 'title_too_long') {
            if ($meta && strlen($meta->title) > 60) {
                 return ['description' => 'Title is too long.', 'evidence' => ['length' => strlen($meta->title)]];
            }
        }
        
        // 4. missing_description
        if ($rule->key === 'missing_description') {
            if (!$meta || empty($meta->description)) {
                return ['description' => 'Meta Description is missing.', 'evidence' => []];
            }
        }

        // 5. non_200_status
        if ($rule->key === 'non_200_status') {
             if ($page->http_status_last && $page->http_status_last !== 200) {
                 return ['description' => "Page returned status {$page->http_status_last}", 'evidence' => ['status' => $page->http_status_last]];
             }
        }

        return null;
    }
}
