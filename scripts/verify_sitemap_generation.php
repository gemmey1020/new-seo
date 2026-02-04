<?php

use App\Models\Site\Site;
use App\Models\Crawl\CrawlRun;
use App\Models\Seo\Page;
use App\Services\SitemapGenerator;
use App\Services\AuthorityService;
use Illuminate\Support\Facades\File;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Mock HealthService to bypass Confidence Gate for this test
$mockHealth = Mockery::mock(\App\Services\HealthService::class);
$mockHealth->shouldReceive('getHealth')->andReturn([
    'confidence' => ['score' => 85, 'level' => 'HIGH'],
    'explanation' => ['drift' => []]
]);
$app->instance(\App\Services\HealthService::class, $mockHealth);

echo "--- VERIFY SITEMAP GENERATION ---\n";

$site = Site::firstOrFail();

// 1. Setup Mock Data (if needed) or Check existing
// Ensure we have a completed crawl run
$run = CrawlRun::where('site_id', $site->id)->where('status', 'completed')->latest()->first();

if (!$run) {
    echo "Creating Mock CrawlRun for testing...\n";
    $run = CrawlRun::create([
        'site_id' => $site->id,
        'mode' => 'active', // or whatever valid mode
        'status' => 'completed',
        'pages_crawled' => 5,
        'started_at' => now(),
        'finished_at' => now()
    ]);
}

// Ensure pages exist with correct status
$page = Page::firstOrCreate(['site_id' => $site->id, 'url' => $site->domain . '/valid-page'], [
    'path' => '/valid-page',
    'http_status_last' => 200,
    'index_status' => 'indexed',
    'last_crawled_at' => now()
]);
// Ensure a noindex page
Page::firstOrCreate(['site_id' => $site->id, 'url' => $site->domain . '/noindex-page'], [
    'path' => '/noindex-page',
    'http_status_last' => 200,
    'index_status' => 'noindex', // Should be excluded
    'last_crawled_at' => now()
]);

// 2. Run Generation
$auth = new AuthorityService();
$generator = new SitemapGenerator($auth);

try {
    echo "Generating Sitemap for Site ID {$site->id}...\n";
    $result = $generator->generate($site);
    
    echo "Status: " . $result['status'] . "\n";
    echo "Path: " . $result['path'] . "\n";
    echo "URLs: " . $result['url_count'] . "\n";
    
    // 3. Verify File
    if (File::exists($result['path'])) {
        echo "[PASS] File exists.\n";
        $content = File::get($result['path']);
        if (strpos($content, '/valid-page') !== false) echo "[PASS] Valid page included.\n";
        else echo "[FAIL] Valid page MISSING.\n";
        
        if (strpos($content, '/noindex-page') === false) echo "[PASS] Noindex page excluded.\n";
        else echo "[FAIL] Noindex page INCLUDED.\n";
    } else {
        echo "[FAIL] File not found.\n";
    }
    
    // 4. Verify Log
    $log = \Illuminate\Support\Facades\DB::table('action_logs')->latest()->first();
    echo "Last Log Action: " . $log->action_type . "\n";
    echo "Last Log Status: " . $log->status . "\n";
    echo "Last Log Reason: " . $log->reason . "\n";
    if ($log->action_type === 'generate_sitemap' && $log->status === 'ALLOWED') {
        echo "[PASS] Action Logged correctly.\n";
    } else {
        echo "[FAIL] Log Mismatch.\n";
    }

} catch (\Exception $e) {
    echo "[ERROR] Generation Failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
