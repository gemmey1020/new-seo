<?php

namespace Tests\Unit\Policy;

use Tests\TestCase;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use App\Services\Policy\PolicyEvaluator;
use Mockery;

class PolicyEnrichmentTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_content_title_enrichment()
    {
        $meta = new SeoMeta();
        $meta->title = 'Short'; // 5 chars

        $page = Mockery::mock(Page::class)->makePartial();
        $page->shouldReceive('relationLoaded')->with('meta')->andReturn(true);
        $page->shouldReceive('getAttribute')->with('meta')->andReturn($meta);
        $page->shouldReceive('getAttribute')->with('analysis')->andReturn([]);
        $page->shouldReceive('getAttribute')->with('structure')->andReturn(['is_orphan' => false]);
        
        $page->h1_count = 1; 
        $page->http_status_last = 200;
        $page->depth_level = 1;

        $evaluator = new PolicyEvaluator();
        $result = $evaluator->evaluate($page);

        $violation = collect($result['violations'])
            ->firstWhere('policy_code', 'CONTENT_TITLE_LENGTH');

        $this->assertNotNull($violation, 'Violation should exist for short title');
        $this->assertEquals(5, $violation['measured_value']);
        $this->assertEquals('10-60 characters', $violation['expected_value']);
        $this->assertEquals('below_minimum', $violation['comparison']);
        $this->assertEquals('high', $violation['confidence']);
        
        $this->assertArrayHasKey('severity_weight', $violation);
        $this->assertArrayHasKey('priority_rank', $violation);
    }
    
    public function test_h1_count_enrichment()
    {
        $page = Mockery::mock(Page::class)->makePartial();
        $page->shouldReceive('relationLoaded')->with('meta')->andReturn(true);
        $page->shouldReceive('getAttribute')->with('meta')->andReturn(new SeoMeta());
        $page->shouldReceive('getAttribute')->with('analysis')->andReturn([]);
        $page->shouldReceive('getAttribute')->with('structure')->andReturn([]);

        $page->h1_count = 3; // Violation
        $page->http_status_last = 200;
        $page->depth_level = 1;

        $evaluator = new PolicyEvaluator();
        $result = $evaluator->evaluate($page);

        $violation = collect($result['violations'])
            ->firstWhere('policy_code', 'CONTENT_H1_COUNT');

        $this->assertNotNull($violation);
        $this->assertEquals(3, $violation['measured_value']);
        $this->assertEquals('1', $violation['expected_value']);
        $this->assertEquals('above_maximum', $violation['comparison']);
    }

    public function test_http_status_enrichment()
    {
        $page = Mockery::mock(Page::class)->makePartial();
        $page->shouldReceive('relationLoaded')->with('meta')->andReturn(true);
        $page->shouldReceive('getAttribute')->with('meta')->andReturn(new SeoMeta());
        $page->shouldReceive('getAttribute')->with('analysis')->andReturn([]);
        $page->shouldReceive('getAttribute')->with('structure')->andReturn([]);

        $page->h1_count = 1;
        $page->http_status_last = 404; // Violation
        $page->depth_level = 1;

        $evaluator = new PolicyEvaluator();
        $result = $evaluator->evaluate($page);

        $violation = collect($result['violations'])
            ->firstWhere('policy_code', 'INDEX_HTTP_STATUS');

        $this->assertNotNull($violation);
        $this->assertEquals(404, $violation['measured_value']);
        $this->assertEquals('200', $violation['expected_value']);
        $this->assertEquals('not_equal', $violation['comparison']);
    }

    public function test_structure_depth_enrichment()
    {
        $page = Mockery::mock(Page::class)->makePartial();
        $page->shouldReceive('relationLoaded')->with('meta')->andReturn(true);
        $page->shouldReceive('getAttribute')->with('meta')->andReturn(new SeoMeta());
        $page->shouldReceive('getAttribute')->with('analysis')->andReturn([]);
        $page->shouldReceive('getAttribute')->with('structure')->andReturn([]);

        $page->h1_count = 1;
        $page->http_status_last = 200;
        $page->depth_level = 5; // Violation

        $evaluator = new PolicyEvaluator();
        $result = $evaluator->evaluate($page);

        $violation = collect($result['violations'])
            ->firstWhere('policy_code', 'STRUCTURE_DEPTH');

        $this->assertNotNull($violation);
        $this->assertEquals(5, $violation['measured_value']);
        $this->assertStringContainsString('3', $violation['expected_value']);
        $this->assertEquals('above_maximum', $violation['comparison']);
    }
}
