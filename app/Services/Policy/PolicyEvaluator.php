<?php

namespace App\Services\Policy;

use App\Models\Seo\Page;

class PolicyEvaluator
{
    /**
     * Evaluate a Page against the Policy Rule Set.
     * 
     * @param Page $page
     * @return array Structured policy verdict
     */
    public function evaluate(Page $page): array
    {
        // Pre-fetch analysis and structure to avoid N+1 or repeated computation
        // Ensure appends are loaded if not already
        if (!$page->relationLoaded('meta')) {
            $page->load('meta');
        }
        
        $analysis = $page->analysis; // Accessor
        $structure = $page->structure; // Accessor

        $rules = PolicyRuleSet::getRules();
        $violations = [];
        $passCount = 0;
        $failCount = 0;

        foreach ($rules as $code => $rule) {
            $callback = $rule['evaluate'];
            $result = $callback($page, $analysis, $structure);

            if ($result['status'] === PolicyRuleSet::STATUS_FAIL) {
                $evidence = $result['evidence'] ?? [];

                $violations[] = [
                    'policy_code' => $code,
                    'severity' => $result['severity'],
                    'field' => $rule['field'],
                    'expected' => 'PASS', // Could be more specific in Rule definition
                    'actual' => 'FAIL',   // Could extract value
                    'explanation' => $result['explanation'],

                    // Evidence fields (J.2 Observability Enrichment)
                    'measured_value' => $evidence['measured_value'] ?? null,
                    'expected_value' => $evidence['expected_value'] ?? null,
                    'comparison' => $evidence['comparison'] ?? null,
                    'confidence' => $evidence['confidence'] ?? 'medium',

                    // Derived metadata (Read-Only)
                    'severity_weight' => $this->getSeverityWeight($result['severity']),
                    'priority_rank' => $this->getPriorityRank($result['severity']),
                ];
                $failCount++;
            } else {
                $passCount++;
            }
        }

        // Determine Aggregate Status
        $aggregateStatus = 'PASS';
        if ($failCount > 0) {
            // If any CRITICAL or HIGH, then FAIL
            // If only WARNING, then WARN
            $severities = array_column($violations, 'severity');
            if (in_array(PolicyRuleSet::SEVERITY_CRITICAL, $severities) || in_array(PolicyRuleSet::SEVERITY_HIGH, $severities)) {
                $aggregateStatus = 'FAIL';
            } elseif (in_array(PolicyRuleSet::SEVERITY_WARNING, $severities)) {
                $aggregateStatus = 'WARN';
            } else {
                $aggregateStatus = 'PASS'; // Optimization/Advisory might typically be PASS or INFO
            }
        }

        return [
            'policy_summary' => [
                'status' => $aggregateStatus,
                'violations_count' => $failCount,
                'rules_evaluated' => count($rules),
                'evaluated_at' => now()->toIso8601String(),
            ],
            'violations' => $violations,
        ];
    }

    private function getSeverityWeight(string $severity): float
    {
        return match (strtoupper($severity)) {
            'CRITICAL' => 1.0,
            'HIGH' => 0.8,
            'WARNING' => 0.6,
            'OPTIMIZATION' => 0.4,
            'ADVISORY' => 0.2,
            default => 0.5,
        };
    }

    private function getPriorityRank(string $severity): int
    {
        return match (strtoupper($severity)) {
            'CRITICAL' => 1,
            'HIGH' => 2,
            'WARNING' => 3,
            'OPTIMIZATION' => 4,
            'ADVISORY' => 5,
            default => 99,
        };
    }
}
