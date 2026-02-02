<?php

use App\Models\Site\Site;
use App\Models\Crawl\CrawlRun;
use App\Services\HealthService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new HealthService();
$sites = Site::all();

echo "| ID | Name | Drift Type | Verdict (Trend) | Status |\n";
echo "| :--- | :--- | :--- | :--- | :--- |\n";

foreach ($sites as $site) {
    if ($site->id <= 4) continue; // Skip the original 4 for the clean table of 25 seeders? Or include all? Let's include all.
    
    // Refresh cache?
    // $key = "site:{$site->id}:health:v1.3";
    // \Illuminate\Support\Facades\Cache::forget($key);
    // Actually, seeder just ran, so cache is empty.
    
    $health = $service->getHealth($site);
    
    $ghost = $health['dimensions']['ghost']['severity'] ?? 'SAFE'; // Not in dims, in drift?
    // Health structure is: dimensions, history, confidence, explanation.
    // 'drift' is not top level in health array returned by getHealth, it's used inside explanation.
    // Wait, let's check HealthService::getHealth return.
    // Line 64: return $healthData;
    // HealthScore DTO (line 5): metrics, score.
    // It does NOT invoke `getDrift` into the final array unless we added it?
    // Line 57: $health->toArray().
    // Line 52: `generateExplanation` uses drift.
    
    $explanation = $health['explanation'] ?? [];
    $negatives = $explanation['negative'] ?? [];
    
    $driftVerdict = "None";
    
    foreach ($negatives as $msg) {
        if (stripos($msg, 'Drift') !== false) {
            // "Critical Ghost Drift detected (>10% 404s) (Persistent)."
            // Extract the text inside parens at end if possible, or just the whole string.
            preg_match('/\((Persistent|Transient|Unknown\/Limited History)\)/', $msg, $matches);
            if (isset($matches[1])) {
                $driftVerdict = $matches[1];
            } else {
                $driftVerdict = "Detected (No Trend)";
            }
        }
    }
    
    // Determine Scenario from Name
    $type = "Unknown";
    if (strpos($site->name, 'Ghost') !== false) $type = 'Ghost (Expected Persistent)';
    elseif (strpos($site->name, 'State') !== false) $type = 'State (Expected Persistent)';
    elseif (strpos($site->name, 'Transient') !== false) $type = 'Transient';
    elseif (strpos($site->name, 'Clean') !== false) $type = 'Clean';
    elseif (strpos($site->name, 'Mixed') !== false) $type = 'Mixed';
    elseif ($site->id <= 4) $type = 'Legacy Verify';

    $match = "✅";
    if (strpos($type, 'Persistent') !== false && $driftVerdict !== 'Persistent') $match = "❌";
    if ($type === 'Transient' && $driftVerdict !== 'Transient') $match = "❌";
    if ($type === 'Clean' && $driftVerdict !== 'None') $match = "❌";

    echo "| {$site->id} | {$site->name} | {$type} | {$driftVerdict} | {$match} |\n";
}
