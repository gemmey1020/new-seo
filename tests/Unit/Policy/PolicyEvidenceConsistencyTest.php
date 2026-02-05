<?php

namespace Tests\Unit\Policy;

use Tests\TestCase;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use App\Services\Policy\PolicyEvaluator;
use Mockery;

class PolicyEvidenceConsistencyTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to extract numeric values from expected_value strings.
     * Normalized to find simple integers like "10", "60".
     */
    private function assertExpectedValueContains(string $expectedValue, array $numbers): void
    {
        foreach ($numbers as $number) {
            $this->assertStringContainsString(
                (string)$number, 
                $expectedValue, 
                "Drift detected! Logic threshold {$number} not found in expected_value string: '{$expectedValue}'"
            );
        }
    }

    public function test_title_length_consistent_thresholds()
    {
        // 1. Create page with length 9 (trigger below min)
        // Rule: min 10
        $page = $this->createMockPage(['meta' => ['title' => str_repeat('a', 9)]]);
        
        $violation = $this->evaluateAndGetViolation($page, 'CONTENT_TITLE_LENGTH');
        
        $this->assertNotNull($violation);
        $this->assertEquals(9, $violation['measured_value']);
        $this->assertEquals('below_minimum', $violation['comparison']);
        $this->assertExpectedValueContains($violation['expected_value'], [10, 60]);

        // 2. Create page with length 61 (trigger above max)
        // Rule: max 60
        $pageMax = $this->createMockPage(['meta' => ['title' => str_repeat('a', 61)]]);
        $violationMax = $this->evaluateAndGetViolation($pageMax, 'CONTENT_TITLE_LENGTH');

        $this->assertNotNull($violationMax);
        $this->assertEquals(61, $violationMax['measured_value']);
        $this->assertEquals('above_maximum', $violationMax['comparison']);
        $this->assertExpectedValueContains($violationMax['expected_value'], [10, 60]);
    }

    public function test_h1_count_consistent_thresholds()
    {
        // Rule: exactly 1
        // Trigger: 2 H1s
        $page = $this->createMockPage([], ['h1_count' => 2]);

        $violation = $this->evaluateAndGetViolation($page, 'CONTENT_H1_COUNT');

        $this->assertNotNull($violation);
        $this->assertEquals(2, $violation['measured_value']);
        $this->assertEquals('above_maximum', $violation['comparison']);
        $this->assertExpectedValueContains($violation['expected_value'], [1]);
    }

    public function test_structure_depth_consistent_thresholds()
    {
        // Rule: max 3
        // Trigger: depth 4
        $page = $this->createMockPage([], ['depth_level' => 4]);

        $violation = $this->evaluateAndGetViolation($page, 'STRUCTURE_DEPTH');

        $this->assertNotNull($violation);
        $this->assertEquals(4, $violation['measured_value']);
        $this->assertEquals('above_maximum', $violation['comparison']);
        $this->assertExpectedValueContains($violation['expected_value'], [3]);
    }

    public function test_http_status_consistent_thresholds()
    {
        // Rule: expected 200
        // Trigger: 404
        $page = $this->createMockPage([], ['http_status_last' => 404]);

        $violation = $this->evaluateAndGetViolation($page, 'INDEX_HTTP_STATUS');

        $this->assertNotNull($violation);
        $this->assertEquals(404, $violation['measured_value']);
        $this->assertEquals('not_equal', $violation['comparison']);
        $this->assertExpectedValueContains($violation['expected_value'], [200]);
    }

    // --- Helpers ---

    private function createMockPage(array $metaOverrides = [], array $attrOverrides = [])
    {
        $meta = new SeoMeta();
        $meta->title = $metaOverrides['meta']['title'] ?? 'Valid Title';
        $meta->description = $metaOverrides['meta']['description'] ?? 'Valid Description';

        $page = Mockery::mock(Page::class)->makePartial();
        $page->shouldReceive('relationLoaded')->with('meta')->andReturn(true);
        $page->shouldReceive('getAttribute')->with('meta')->andReturn($meta);
        $page->shouldReceive('getAttribute')->with('analysis')->andReturn([]);
        $page->shouldReceive('getAttribute')->with('structure')->andReturn(['is_orphan' => false]);
        
        // Defaults
        $page->h1_count = $attrOverrides['h1_count'] ?? 1;
        $page->http_status_last = $attrOverrides['http_status_last'] ?? 200;
        $page->depth_level = $attrOverrides['depth_level'] ?? 1;
        $page->url = 'http://example.com';

        return $page;
    }

    private function evaluateAndGetViolation($page, $code)
    {
        $evaluator = new PolicyEvaluator();
        $result = $evaluator->evaluate($page);
        
        return collect($result['violations'])
            ->firstWhere('policy_code', $code);
    }
}
