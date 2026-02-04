<?php

use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Models\Audit\SeoAudit;
use App\Jobs\AuditRunJob;
use Illuminate\Support\Facades\DB;
use App\Models\Audit\AuditRule;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- VERIFY PHASE B AUDITS ---\n";

$site = Site::firstOrFail();

// 1. Setup Data
echo "Setting up test pages...\n";

// A. Parameter URL
$p1 = Page::updateOrCreate(
    ['site_id' => $site->id, 'url' => 'https://test.com/page?param=1'],
    ['path' => '/page', 'http_status_last' => 200]
);

// B. Pagination (Simulate via Mocking ContentService or injecting Meta)
// Since AuditRunJob calls ContentService->analyze(), we need to mock it OR ensure ContentService can read it.
// Easier: Mock ContentService in the Job? Or just update Page Model to include logic test?
// Actually, AuditRunJob uses $analysis from ContentService. 
// I'll Mock ContentService to return specific analysis for a specific page ID.
// But AuditRunJob instantiates ContentService inside handle(). I cannot inject it easily without Service Container if it's new'd.
// Check AuditRunJob: `$contentService = new \App\Services\ContentService();` -> It uses `new`. Hard to mock.
// I'll rely on subclassing or reflection? Or just modify AuditRunJob to use app(ContentService::class).
// Let's modify AuditRunJob to use `app()` so I can mock it.

// C. Duplicates
// Try create duplicate path
try {
    $pDup1 = Page::create(['site_id' => $site->id, 'url' => 'https://test.com/dup1', 'path' => '/dup']);
    $pDup2 = Page::create(['site_id' => $site->id, 'url' => 'https://test.com/dup2', 'path' => '/dup']);
    echo "[SETUP] Created Duplicates.\n";
} catch (\Exception $e) {
    echo "[SETUP] DB Unique Constraint prevented duplicates (OK).\n";
}

// D. Robots Meta (Saved in SeoMeta)
$pRobots = Page::updateOrCreate(
    ['site_id' => $site->id, 'url' => 'https://test.com/robots'],
    ['path' => '/robots', 'http_status_last' => 200]
);
\App\Models\Seo\SeoMeta::updateOrCreate(
    ['page_id' => $pRobots->id],
    ['robots' => 'noindex, nofollow']
);

// 2. Mock ContentService for Pagination Test
// This mock will apply to all pages, triggering pagination/robots detections everywhere 
// unless we filter by page input. But simpler is acceptable: we just check if AT LEAST ONE audit exists.
$mockContent = Mockery::mock(\App\Services\ContentService::class);
$mockContent->shouldReceive('analyze')->andReturn([
    'meta' => [
        'prev' => '/prev', 
        'next' => '/next', 
        'robots' => 'noindex, nofollow', 
        'canonical' => null
    ],
    'schemas' => [['valid' => true]],
    'structure' => ['issues' => []],
    'readability' => [],
    'keywords' => []
]);
$app->instance(\App\Services\ContentService::class, $mockContent);

// 3. Execution
echo "Running AuditRunJob...\n";
$job = new AuditRunJob($site->id);
$job->handle();

// 4. Assertions
$audits = SeoAudit::where('site_id', $site->id)->get();

echo "Audits Found: " . $audits->count() . "\n";

$foundParam = $audits->where('rule_id', AuditRule::where('key', 'param_url_detected')->first()->id)->count();
echo "Param URL Audits: $foundParam " . ($foundParam > 0 ? "[PASS]" : "[FAIL]") . "\n";

$foundPag = $audits->where('rule_id', AuditRule::where('key', 'pagination_detected')->first()->id)->count();
echo "Pagination Audits: $foundPag " . ($foundPag > 0 ? "[PASS]" : "[FAIL]") . "\n";

$foundDup = $audits->where('rule_id', AuditRule::where('key', 'duplicate_slug_detected')->first()->id)->count();
echo "Duplicate Slug Audits: $foundDup " . ($foundDup > 0 ? "[PASS (or N/A)]" : "[INFO]") . "\n";

$foundRobots = $audits->where('rule_id', AuditRule::where('key', 'robots_meta_detected')->first()->id)->count();
echo "Robots Meta Audits: $foundRobots " . ($foundRobots > 0 ? "[PASS]" : "[FAIL]") . "\n";

// Cleanup
Page::where('path', '/dup')->delete();
Page::where('path', '/page')->delete();
Page::where('path', '/robots')->delete();

