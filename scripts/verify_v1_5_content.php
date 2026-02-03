<?php

use Illuminate\Support\Facades\Cache;
use App\Models\Site\Site;
use App\Models\Seo\Page;
use App\Services\ContentService;
use App\Models\Crawl\CrawlRun;
use App\Models\Crawl\CrawlLog;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "--- VERIFY v1.5 CONTENT SERVICE ---\n";

// 1. Setup Data
$site = Site::firstOrFail();
$page = Page::firstOrCreate(
    ['site_id' => $site->id, 'url' => 'http://v1-5-test.local'],
    ['path' => '/', 'index_status' => 'indexed']
);

$run = CrawlRun::create(['site_id' => $site->id, 'mode' => 'crawl', 'status' => 'completed']);
$log = CrawlLog::create([
    'site_id' => $site->id,
    'page_id' => $page->id,
    'crawl_run_id' => $run->id,
    'status_code' => 200,
    'crawled_at' => now(),
]);

// 2. Mock Content in Cache
$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
    <link rel="canonical" href="http://other-url.local">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Test Org"
    }
    </script>
    <script type="application/ld+json">
    {
        "@type": "BrokenJson",
        "name": "Test"
    }
    </script> 
    <!-- Note: Broken JSON above is valid syntax string but encoded correctly here?? 
         Wait, literally passing brace inside string might be valid JSON if escaped. 
         Let's make it actually invalid JSON string syntax for test. -->
</head>
<body>
    <h1>Main Title</h1>
    <p>This is a paragraph of text to check readability.</p>
    <!-- Skip H2 -->
    <h3>Subsection (Skipped H2)</h3>
</body>
</html>
HTML;

// We need to inject invalid JSON manually because HEREDOC is string.
// Let's replace "BrokenJson" block with actual invalid syntax
$html = str_replace(
    '"name": "Test"',
    '"name": "Test",,', // Syntax error
    $html
);

$cacheKey = "crawl_body_{$run->id}_{$page->id}";
Cache::put($cacheKey, $html, 300);
echo "1. Data Setup & Cache Mock: OK\n";

// 3. Run Analysis
$service = new ContentService();
echo "2. Running ContentService->analyze()...\n";
$result = $service->analyze($page);

// 4. Assertions

// A. Readability
if ($result['readability']['metrics']['word_count'] > 0) {
    echo "   [PASS] Readability calculated (Words: {$result['readability']['metrics']['word_count']})\n";
} else {
    echo "   [FAIL] Readability missing.\n";
}

// B. Structure
echo "3. Verifying Structure Logic...\n";
$issues = $result['structure']['issues'];
$hasSkip = false;
foreach ($issues as $issue) {
    if (strpos($issue, 'Skipped heading') !== false) $hasSkip = true;
    echo "   Found Issue: $issue\n";
}
if ($hasSkip) echo "   [PASS] Detected Skipped Heading Level.\n";
else echo "   [FAIL] Did not detect skipped heading.\n";

// C. Meta (Canonical)
echo "4. Verifying Canonical Extraction...\n";
$canon = $result['meta']['canonical'];
if ($canon === "http://other-url.local") {
    echo "   [PASS] Canonical extracted: $canon\n";
} else {
    echo "   [FAIL] Expected http://other-url.local, got " . ($canon ?? 'null') . "\n";
}

// D. Schema
echo "5. Verifying Schema Validation...\n";
$schemas = $result['schemas'];
$validCount = 0;
$invalidCount = 0;
foreach ($schemas as $s) {
    if ($s['valid']) {
        $validCount++;
        echo "   Schema (Valid): {$s['type']}\n";
    } else {
        $invalidCount++;
        echo "   Schema (Invalid): Error: {$s['error']}\n";
    }
}

if ($validCount === 1 && $invalidCount === 1) {
    echo "   [PASS] Correctly identified 1 Valid and 1 Invalid schema.\n";
} else {
    echo "   [FAIL] Expected 1 Valid, 1 Invalid. Got V:$validCount I:$invalidCount\n";
}

// Cleanup
$log->delete(); // Keep Page/Site/Run for now or delete if needed. 
// Just leave them, safe.

echo "\n--- VERIFICATION COMPLETE ---\n";
