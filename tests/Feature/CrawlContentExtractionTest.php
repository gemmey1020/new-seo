<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\Site\Site;
use App\Models\Crawl\CrawlRun;
use App\Models\Seo\Page;
use App\Models\Seo\SeoMeta;
use App\Jobs\CrawlRunJob;

class CrawlContentExtractionTest extends TestCase
{
    // use RefreshDatabase; // We might want to avoid wiping if using shared DB, but for test correctness it's usually good. 
    // Given the environment, I'll allow it to run on the configured DB but manual cleanup is safer if we want to preserve state.
    // Actually, I'll manually clean up the specific test site to be safe.

    public function test_crawl_extracts_and_persists_content()
    {
        // 1. Setup
        $domain = 'extraction-test.com';
        Site::where('domain', $domain)->delete();
        
        $site = Site::create([
            'name' => 'Extraction Test',
            'domain' => $domain,
            'is_active' => true,
        ]);

        $run = CrawlRun::create([
            'site_id' => $site->id,
            'mode' => 'full',
            'user_agent' => 'TestBot',
            'status' => 'pending',
            'pages_discovered' => 0,
            'pages_crawled' => 0,
            'errors_count' => 0,
        ]);

        // 2. Mock HTTP
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Extracted Title</title>
    <meta name="description" content="Extracted Description">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="https://extraction-test.com/canonical-page">
</head>
<body>
    <h1>Main Header</h1>
    <p>Some content</p>
    <img src="/logo.png" alt="Logo">
    <img src="https://other.com/image.jpg" alt="External">
    <a href="/internal-link">Internal</a>
</body>
</html>
HTML;

        Http::fake([
            'extraction-test.com/*' => Http::response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Robots-Tag' => 'noarchive',
            ]),
        ]);

        // 3. Execution
        $job = new CrawlRunJob($run->id);
        $job->handle();

        // 4. Assertions
        $run->refresh();
        $this->assertEquals('completed', $run->status);
        $this->assertEquals(2, $run->pages_discovered); // Homepage + internal link

        // Check Homepage
        $home = Page::where('site_id', $site->id)->where('path', '/')->first();
        $this->assertNotNull($home);
        $this->assertEquals(1, $home->h1_count);
        $this->assertEquals(2, $home->image_count);
        $this->assertGreaterThan(0, $home->content_bytes);

        // Check Meta
        $meta = SeoMeta::where('page_id', $home->id)->first();
        $this->assertNotNull($meta);
        $this->assertEquals('Extracted Title', $meta->title);
        $this->assertEquals('Extracted Description', $meta->description);
        $this->assertEquals('noindex, nofollow', $meta->robots);
        $this->assertEquals('noarchive', $meta->robots_header);
        $this->assertEquals('https://extraction-test.com/canonical-page', $meta->canonical_extracted);
        $this->assertEquals('Main Header', $meta->h1_first_text);
        
        $this->assertIsArray($meta->images_sample_json);
        $this->assertCount(2, $meta->images_sample_json);
        $this->assertContains('/logo.png', $meta->images_sample_json);

        // Cleanup
        $site->delete(); // Cascades
    }
}
