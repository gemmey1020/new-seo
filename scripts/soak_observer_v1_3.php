<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Site\Site;
use App\Services\HealthService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$site = Site::firstOrFail();
$service = new HealthService();

echo "--- SOAK OBSERVER v1.3 ---\n";
echo "Date: " . now()->toIso8601String() . "\n";
echo "Site ID: {$site->id}\n\n";

// 1. PERFORMANCE TEST
echo "[1] PERFORMANCE (Latency)\n";
$start = microtime(true);
$health = $service->getHealth($site); // Potentially Uncached or Cached
$end = microtime(true);
$ms = round(($end - $start) * 1000, 2);
echo "   Call 1 (Unknown state): {$ms}ms\n";

// Force Cache Hit Check strictly by calling again immediately
$start = microtime(true);
$service->getHealth($site);
$end = microtime(true);
$msCached = round(($end - $start) * 1000, 2);
echo "   Call 2 (Cached)       : {$msCached}ms\n";

if ($msCached > 50) {
    echo "   WARNING: Cached latency > 50ms requirement!\n";
} else {
    echo "   PASS: Performance within budget.\n";
}

// 2. DATA BEHAVIOR & CONFIDENCE
echo "\n[2] DATA BEHAVIOR\n";
$conf = $health['confidence'];
echo "   Confidence Score: {$conf['score']} ({$conf['level']})\n";
if (!empty($conf['reasons'])) {
    echo "   Reasons: " . implode(", ", $conf['reasons']) . "\n";
}

$historyCount = count($health['history']);
echo "   History Depth: {$historyCount} runs\n";
// Sanity check: If historyCount < 3, Level should NOT be HIGH (unless covered by massive crawl factor?)
// Formula: (Crawl * 50) + (Hits * 20 max 100 * 0.5) -> Hits contribution is max 50.
// If History < 3 (Run=2), Factor is 0.4. Max Score = 50 + 20 = 70 (MEDIUM).
// So HIGH requires History >= 3 (Run=3 -> 0.6*50 = 30 + 50 = 80).
// Correct.

// 3. DRIFT ACCURACY & NOISE
echo "\n[3] DRIFT & NOISE\n";
$drift = $health['metrics']['drift'] ?? $service->getDrift($site); // Drift is separate in service but part of process
// Wait, getHealth returns DTO array, drift is NOT in 'metrics', it's calculated inside but DTO structure in HC-002 only had 'history' added to wrapper, 
// HC-003 says "explanation" uses drift. 
// Drifting is NOT strictly returned in the `getHealth` root array in HC-002/3 unless I missed it.
// Checking HealthService code...
// `getHealth` returns `$healthData` which is `HealthScore->toArray()` + history + confidence + explanation.
// It DOES NOT return `drift` explicitly in the root. The drift is used to generate `explanation`.
// However, the `DriftReport` is available via `getDrift`.

$driftObj = $service->getDrift($site);
$indicators = $driftObj['indicators'];
foreach ($indicators as $key => $ind) {
    echo "   [$key] Severity: {$ind['severity']} (Count: {$ind['count']})\n";
}

// 4. EXPLAINABILITY
echo "\n[4] EXPLAINABILITY\n";
$expl = $health['explanation'];
echo "   Summary: {$expl['summary']}\n";
echo "   Positive:\n";
foreach ($expl['positive'] as $p) echo "    + $p\n";
echo "   Negative:\n";
foreach ($expl['negative'] as $n) echo "    - $n\n";

// 5. READINESS CHECK
echo "\n[5] READINESS GATE\n";
$readiness = $service->getReadiness($site);
echo "   Ready: " . ($readiness['ready'] ? "YES" : "NO") . "\n";
if (!$readiness['ready']) {
    echo "   Blockers: " . implode(", ", $readiness['blockers']) . "\n";
    echo "   Message: {$readiness['message']}\n";
}

echo "\n--- RAW LOG ENTRY START ---\n";
echo json_encode([
    'date' => now()->toIso8601String(),
    'score' => $health['score'],
    'confidence' => $conf,
    'drift_status' => $driftObj['status'],
    'latency_ms' => $msCached
]);
echo "\n--- RAW LOG ENTRY END ---\n";
