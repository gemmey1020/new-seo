<?php

namespace App\Services\Analysis;

use App\Models\Seo\Page;

/**
 * Class PageSeoAnalyzer
 * 
 * Derived Analysis Layer (Read-Only).
 * Computes SEO health, indexability, and issues from persisted state.
 * 
 * PURE FUNCTION: Input Page -> Output Analysis Array
 */
class PageSeoAnalyzer
{
    /**
     * Analyze a page and return full SEO report.
     */
    public function analyze(Page $page): array
    {
        $issues = [];
        $meta = $page->meta;
        
        // 1. Indexability Verdict
        $indexable = true;
        $indexStatus = 'Indexable';
        
        // Check HTTP Status
        if ($page->http_status_last !== 200) {
            $indexable = false;
            $indexStatus = "Non-200 Status ({$page->http_status_last})";
        }
        
        // Check Robots Meta
        if ($meta && str_contains(strtolower($meta->robots ?? ''), 'noindex')) {
            $indexable = false;
            $indexStatus = 'Blocked by Robots Meta';
        }
        
        // Check Canonical
        // If canonical exists and is different from self, it's canonicalized (not indexed as self)
        if ($meta && !empty($meta->canonical_extracted)) {
            $selfUrl = rtrim($page->url, '/');
            $canonicalUrl = rtrim($meta->canonical_extracted, '/');
            
            if ($selfUrl !== $canonicalUrl) {
                // If canonical points elsewhere, it's technically "indexable" in that it's 200, 
                // but effectively "canonicalized" so not the primary version.
                // For simplified "Indexable" boolean, we usually say FALSE if it's canonicalized to another URL.
                $indexable = false;
                $indexStatus = 'Canonicalized to other URL';
            }
        }

        // 2. Issue Detection
        
        // Critical: Title
        if (!$meta || empty($meta->title)) {
            $issues[] = [
                'code' => 'MISSING_TITLE',
                'severity' => 'critical',
                'message' => 'Page is missing a Title tag.'
            ];
        } elseif (strlen($meta->title) < 10) {
            $issues[] = [
                'code' => 'SHORT_TITLE',
                'severity' => 'warning',
                'message' => 'Title is too short (< 10 chars).'
            ];
        } elseif (strlen($meta->title) > 60) {
            $issues[] = [
                'code' => 'LONG_TITLE',
                'severity' => 'optimization',
                'message' => 'Title is too long (> 60 chars).'
            ];
        }

        // Important: Description
        if (!$meta || empty($meta->description)) {
            $issues[] = [
                'code' => 'MISSING_DESC',
                'severity' => 'high',
                'message' => 'Page is missing a Meta Description.'
            ];
        }

        // Structure: H1
        if ($page->h1_count === 0) {
            $issues[] = [
                'code' => 'MISSING_H1',
                'severity' => 'high',
                'message' => 'Page has no H1 heading.'
            ];
        } elseif ($page->h1_count > 1) {
            $issues[] = [
                'code' => 'MULTIPLE_H1',
                'severity' => 'optimization',
                'message' => 'Page has multiple H1 headings.'
            ];
        }

        // Content: Images
        if ($page->image_count > 0 && $meta && !empty($meta->images_sample_json)) {
            // Check for missing alt text would go here if we extracted it. 
            // For now, simple check.
        }

        // 3. Score Calculation
        $baseScore = 100;
        foreach ($issues as $issue) {
            switch ($issue['severity']) {
                case 'critical': $baseScore -= 20; break;
                case 'high': $baseScore -= 10; break;
                case 'warning': $baseScore -= 5; break;
                case 'optimization': $baseScore -= 2; break;
            }
        }
        $score = max(0, $baseScore);

        return [
            'score' => $score,
            'indexable' => $indexable,
            'index_status' => $indexStatus,
            'issues' => $issues
        ];
    }
}
