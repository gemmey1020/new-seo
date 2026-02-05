<?php

namespace App\Services\Policy;

class PolicyRuleSet
{
    public const SEVERITY_CRITICAL = 'CRITICAL';
    public const SEVERITY_HIGH = 'HIGH';
    public const SEVERITY_WARNING = 'WARNING';
    public const SEVERITY_ADVISORY = 'ADVISORY';
    public const SEVERITY_OPTIMIZATION = 'OPTIMIZATION';

    public const STATUS_PASS = 'PASS';
    public const STATUS_FAIL = 'FAIL';

    /**
     * Get all policy rules definition.
     * 
     * @return array
     */
    public static function getRules(): array
    {
        return [
            // CONTENT POLICIES
            'CONTENT_TITLE_LENGTH' => [
                'category' => 'content',
                'field' => 'meta.title',
                'evaluate' => function ($page, $analysis) {
                    $len = strlen($page->meta->title ?? '');
                    if ($len === 0) return self::violation(self::SEVERITY_HIGH, 'Title is missing.', 0, '10-60 characters', 'missing', 'high');
                    if ($len < 10) return self::violation(self::SEVERITY_WARNING, 'Title is too short (< 10 chars).', $len, '10-60 characters', 'below_minimum', 'high');
                    if ($len > 60) return self::violation(self::SEVERITY_OPTIMIZATION, 'Title is too long (> 60 chars).', $len, '10-60 characters', 'above_maximum', 'high');
                    return self::pass();
                }
            ],
            'CONTENT_META_DESC' => [
                'category' => 'content',
                'field' => 'meta.description',
                'evaluate' => function ($page, $analysis) {
                    $len = strlen($page->meta->description ?? '');
                    if ($len === 0) return self::violation(self::SEVERITY_HIGH, 'Meta description is missing.');
                    return self::pass();
                }
            ],
            'CONTENT_H1_COUNT' => [
                'category' => 'content',
                'field' => 'h1_count',
                'evaluate' => function ($page, $analysis) {
                    // Use model attribute directly as it is raw data
                    $count = $page->h1_count;
                    if ($count === 0) return self::violation(self::SEVERITY_HIGH, 'Page has no H1 heading.', 0, '1', 'missing', 'high');
                    if ($count > 1) return self::violation(self::SEVERITY_OPTIMIZATION, 'Multiple H1 headings found (should be 1).', $count, '1', 'above_maximum', 'high');
                    return self::pass();
                }
            ],

            // STRUCTURE POLICIES
            'STRUCTURE_ORPHAN' => [
                'category' => 'structure',
                'field' => 'structure.is_orphan',
                'evaluate' => function ($page, $analysis, $structure) {
                    if (($structure['is_orphan'] ?? false) === true) {
                        return self::violation(self::SEVERITY_HIGH, 'Page is an orphan (no internal inbound links).');
                    }
                    return self::pass();
                }
            ],
            'STRUCTURE_DEPTH' => [
                'category' => 'structure',
                'field' => 'depth_level',
                'evaluate' => function ($page, $analysis, $structure) {
                    if ($page->depth_level > 3) {
                        return self::violation(self::SEVERITY_WARNING, 'Page depth is greater than 3 clicks from home.', $page->depth_level, '<= 3 clicks', 'above_maximum', 'high');
                    }
                    return self::pass();
                }
            ],

            // INDEXABILITY POLICIES
            'INDEX_HTTP_STATUS' => [
                'category' => 'indexability',
                'field' => 'http_status_last',
                'evaluate' => function ($page, $analysis) {
                    if ($page->http_status_last !== 200) {
                        return self::violation(self::SEVERITY_CRITICAL, "HTTP Status is {$page->http_status_last} (expected 200).", $page->http_status_last, '200', 'not_equal', 'high');
                    }
                    return self::pass();
                }
            ],
            'INDEX_CANONICAL' => [
                'category' => 'indexability',
                'field' => 'analysis.canonical_status',
                // Assuming analysis layer determines if it's canonicalized away
                'evaluate' => function ($page, $analysis) {
                   // Logic inferred from spec: indexable === false && index_status === Canonicalized
                   // analysis array likely has this or we infer from model fields
                   // Let's use page->index_status if available or analysis
                   
                   // Check raw meta or analysis
                   // If canonical exists and differs from current URL
                   $canonical = $page->meta->canonical_extracted ?? null;
                   if ($canonical && $canonical !== $page->url) {
                       return self::violation(self::SEVERITY_ADVISORY, 'Page is canonicalized to another URL.');
                   }
                   return self::pass();
                }
            ],
            'INDEX_ROBOTS' => [
                'category' => 'indexability',
                'field' => 'meta.robots',
                'evaluate' => function ($page, $analysis) {
                    $robots = strtolower($page->meta->robots ?? '');
                    if (str_contains($robots, 'noindex')) {
                        return self::violation(self::SEVERITY_ADVISORY, 'Page has "noindex" directive.');
                    }
                    return self::pass();
                }
            ],
        ];
    }

    private static function violation(
        string $severity, 
        string $explanation,
        mixed $measured = null,
        mixed $expected = null,
        ?string $comparison = null,
        ?string $confidence = null
    ): array
    {
        $result = [
            'status' => self::STATUS_FAIL,
            'severity' => $severity,
            'explanation' => $explanation,
        ];

        if ($measured !== null) {
            $result['evidence'] = [
                'measured_value' => $measured,
                'expected_value' => $expected,
                'comparison' => $comparison,
                'confidence' => $confidence,
            ];
        }

        return $result;
    }

    private static function pass(): array
    {
        return ['status' => self::STATUS_PASS];
    }
}
