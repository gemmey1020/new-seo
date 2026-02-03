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
        $contentService = new \App\Services\ContentService();

        foreach ($pages as $page) {
            $contentAnalysis = null; // Lazy load per page

            foreach ($rules as $rule) {
                // Pass by reference or just pass the analysis var
                // We need to pass $contentAnalysis so checkRule can use it or populate it?
                // checkRule is protected. We can refactor loop to populate it here.
                
                // Check if rule is "deep"
                $isDeepRule = in_array($rule->key, ['canonical_mismatch', 'heading_hierarchy', 'schema_error']);

                if ($isDeepRule && $contentAnalysis === null) {
                    $contentAnalysis = $contentService->analyze($page);
                }

                $violation = $this->checkRule($rule, $page, $contentAnalysis);
                
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

    protected function checkRule(AuditRule $rule, Page $page, ?array $analysis = null)
    {
        $meta = $page->meta;
        
        // --- v1.0 Rules ---

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

        // --- v1.5 Deep Rules ---
        
        if ($analysis && isset($analysis['error'])) {
             // If content fetch failed, we can't audit deep rules. Skip/Silent or Report?
             return null; 
        }

        // 6. canonical_mismatch
        if ($rule->key === 'canonical_mismatch' && $analysis) {
            $canonical = $analysis['meta']['canonical'] ?? null;
            // Normalize URLs for comparison (trim slash)
            $pageUrl = rtrim($page->url, '/');
            $canonUrl = rtrim($canonical, '/');

            if ($canonical && $pageUrl !== $canonUrl) {
                return [
                    'description' => 'Canonical URL does not match Page URL.',
                    'evidence' => ['page' => $pageUrl, 'canonical' => $canonical]
                ];
            }
        }

        // 7. heading_hierarchy
        if ($rule->key === 'heading_hierarchy' && $analysis) {
            if (!empty($analysis['structure']['issues'])) {
                return [
                    'description' => 'Heading hierarchy issues detected.',
                    'evidence' => ['issues' => $analysis['structure']['issues']]
                ];
            }
        }

        // 8. schema_error
        if ($rule->key === 'schema_error' && $analysis) {
             foreach ($analysis['schemas'] as $schema) {
                 if (!$schema['valid']) {
                     return [
                         'description' => 'Structured Data (JSON-LD) syntax error.',
                         'evidence' => ['error' => $schema['error']]
                     ];
                 }
             }
        }

        return null;
    }
}
