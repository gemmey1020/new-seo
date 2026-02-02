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

echo "Found " . $sites->count() . " sites.\n\n";

foreach ($sites as $site) {
    echo "--------------------------------------------------\n";
    echo "SITE: {$site->url} (ID: {$site->id})\n";
    
    // Clear cache to ensure fresh calc if needed, but Audit expects 'system behavior', so maybe keep cache?
    // The user wants 'API output', so let's stick to cached if available, but for the purpose of 'Validation', 
    // we want to see what the system says. The Service uses cache.
    // However, I'll calculate specific data manually to cross-reference.
    
    $health = $service->getHealth($site);
    
    // Extract Drift Info from Explanation
    $negative = $health['explanation']['negative'] ?? [];
    $driftMentions = array_filter($negative, fn($n) => stripos($n, 'Drift') !== false);
    
    // Also check the raw drift object if possible context needed, but getHealth returns array.
    // The service has `getDrift` public method too.
    $drift = $service->getDrift($site);
    
    $ghostSeverity = $drift['indicators']['ghost']['severity'] ?? 'SAFE';
    $stateSeverity = $drift['indicators']['state']['severity'] ?? 'SAFE';
    
    echo "Ghost Severity: $ghostSeverity\n";
    echo "State Severity: $stateSeverity\n";
    
    if (empty($driftMentions)) {
        echo "System Verdict: NO DRIFT\n";
    } else {
        foreach ($driftMentions as $msg) {
            echo "System Verdict: $msg\n";
        }
    }

    // Now get the specific pages causing issues in the LATEST run
    $lastRun = CrawlRun::where('site_id', $site->id)->latest()->first();
    if ($lastRun) {
        echo "Last Run ID: {$lastRun->id} ({$lastRun->created_at})\n";
        
        // Ghost Samples (>= 400)
        $ghosts = DB::table('crawl_logs')
            ->where('crawl_run_id', $lastRun->id)
            ->where('status_code', '>=', 400)
            ->limit(3)
            ->get(['final_url', 'status_code']);
            
        if ($ghosts->isNotEmpty()) {
            echo "Ghost Samples:\n";
            foreach ($ghosts as $g) {
                echo " - [{$g->status_code}] {$g->final_url}\n";
            }
        }
        
        // State Samples (!= 200)
        $states = DB::table('crawl_logs')
            ->where('crawl_run_id', $lastRun->id)
            ->where('status_code', '!=', 200)
            ->limit(3)
            ->get(['final_url', 'status_code']);
            
        if ($states->isNotEmpty()) {
            echo "State Samples:\n";
            foreach ($states as $g) {
                echo " - [{$g->status_code}] {$g->final_url}\n";
            }
        }
    } else {
        echo "No runs found.\n";
    }
    echo "\n";
}
