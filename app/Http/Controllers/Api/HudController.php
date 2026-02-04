<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site\Site;
use App\Services\HealthService;
use Illuminate\Http\Request;

class HudController extends Controller
{
    protected $health;

    public function __construct(HealthService $health)
    {
        $this->health = $health;
    }

    /**
     * Get the System Truth for the HUD.
     * strictly Read-Only.
     */
    public function state(Request $request)
    {
        // Context: Single Site (or passed ID)
        $site = Site::firstOrFail();

        // 1. Check Lock State (Kill Switch)
        $authorityEnabled = env('AUTHORITY_ENABLED', false) === true;
        
        if (!$authorityEnabled) {
            return response()->json([
                'system_status' => 'locked',
                'context' => 'Authority Globally Disabled (v1.5 Mode)',
                'health' => null // locked systems don't need real-time health visualization in this zone
            ]);
        }

        // 2. Check Health (Risk Assessment)
        $health = $this->health->getHealth($site);
        $score = $health['confidence']['score'] ?? 0;
        
        // Critical Drifts check
        $ghostDrift = $health['explanation']['drift']['indicators']['ghost']['severity'] ?? 'SAFE';

        $status = 'stable';
        if ($score < 50 || $ghostDrift === 'CRITICAL') {
            $status = 'risk';
        } elseif ($score < 80) {
            $status = 'attention';
        }

        return response()->json([
            'system_status' => $status,
            'context' => [
                'why' => $health['explanation']['summary'] ?? 'System Observing',
                'risk_factors' => $health['explanation']['negative'] ?? [],
                'trend' => $this->calculateTrend($health['history'] ?? [])
            ],
            'health_score' => $score,
            'authority_enabled' => true // kept for legacy compat if needed, but context has richer data now
        ]);
    }

    /**
     * Get the Simulation Preview (Ghost).
     * Strictly Read-Only.
     */
    public function simulate(Request $request)
    {
        $site = Site::firstOrFail();
        $health = $this->health->getHealth($site);
        
        $currentScore = $health['confidence']['score'] ?? 0;
        
        // Logic: Simulate fixing "Critical" issues.
        // If we have critical drift, fixing it would improve stability/compliance.
        // This is a naive heuristic for the "Ghost" visualization.
        
        $projectedScore = $currentScore;
        $delta = [];

        // 1. Simulate Drift Fix
        if (($health['explanation']['drift']['indicators']['ghost']['severity'] ?? 'SAFE') === 'CRITICAL') {
            $projectedScore += 20; // Hypothetical boost
            $delta[] = 'Resolve Ghost Drift';
        }

        // 2. Simulate Compliance Fix
        if (($health['dimensions']['compliance']['metrics']['critical_audits'] ?? 0) > 0) {
            $projectedScore += 15;
            $delta[] = 'Fix Critical Audits';
        }

        $projectedScore = min(100, $projectedScore);

        return response()->json([
            'current_state' => "Health: {$currentScore}%",
            'projected_state' => "Health: {$projectedScore}%",
            'delta_summary' => count($delta) > 0 ? implode(', ', $delta) : 'No Critical Actions Available'
        ]);
    }

    /**
     * Commit the Simulation (Execution).
     * PHASE J: REAL MUTATION.
     */
    public function commit(Request $request)
    {
        // GATE 1: Authority Lock
        if (env('AUTHORITY_ENABLED', false) !== true) {
            return response()->json(['status' => 'error', 'message' => 'System Locked - Authority Disabled'], 403);
        }

        $site = Site::firstOrFail();

        // GATE 2: Re-Simulation (Verify Issues Exist)
        $health = $this->health->getHealth($site);
        $ghostDrift = $health['explanation']['drift']['indicators']['ghost']['severity'] ?? 'SAFE';
        $criticalAudits = $health['dimensions']['compliance']['metrics']['critical_audits'] ?? 0;

        if ($ghostDrift !== 'CRITICAL' && $criticalAudits === 0) {
            return response()->json(['status' => 'success', 'message' => 'Nothing to Fix', 'actions' => []], 200);
        }

        // Log BEFORE State
        $beforeState = [
            'ghost_severity' => $ghostDrift,
            'critical_audits' => $criticalAudits,
            'timestamp' => now()->toIso8601String()
        ];
        \Illuminate\Support\Facades\Log::info("HUD COMMIT: BEFORE", $beforeState);

        // Mutation Execution
        $actionsPerformed = [];

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // ACTION 1: Resolve Ghost Drift (Pages with HTTP >= 400)
            if ($ghostDrift === 'CRITICAL') {
                $affectedCount = \App\Models\Seo\Page::where('site_id', $site->id)
                    ->where('http_status_last', '>=', 400)
                    ->update(['http_status_last' => null]); // Reset status to force re-crawl
                $actionsPerformed[] = "Resolved Ghost Drift: {$affectedCount} pages marked for re-verification.";
            }

            // ACTION 2: Resolve Critical Audits (Close all 'critical' severity open audits)
            if ($criticalAudits > 0) {
                $closedCount = \App\Models\Audit\SeoAudit::where('site_id', $site->id)
                    ->where('status', 'open')
                    ->where('severity', 'critical')
                    ->update(['status' => 'acknowledged', 'resolved_at' => now()]);
                $actionsPerformed[] = "Closed Critical Audits: {$closedCount} audits acknowledged.";
            }

            // DRY-RUN GATES
            if (env('HUD_DRY_RUN', false) === true) {
                \Illuminate\Support\Facades\DB::rollBack();
                \Illuminate\Support\Facades\Log::info("HUD DRY-RUN: Transaction Rolled Back (Safety Active)");
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Dry Run Complete (No Mutation)',
                    'actions' => $actionsPerformed,
                    'timestamp' => now()->toIso8601String(),
                    'dry_run' => true
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            // Clear Health Cache (Force Fresh Calculation)
            \Illuminate\Support\Facades\Cache::forget("site:{$site->id}:health:v1.3");
            \Illuminate\Support\Facades\Cache::forget("site:{$site->id}:drift:v1.3");

            // Log AFTER State
            $afterState = [
                'actions' => $actionsPerformed,
                'timestamp' => now()->toIso8601String()
            ];
            \Illuminate\Support\Facades\Log::info("HUD COMMIT: AFTER", $afterState);

            return response()->json([
                'status' => 'success',
                'message' => 'Execution Complete',
                'actions' => $actionsPerformed,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::critical("HUD COMMIT: FAILED", ['error' => $e->getMessage()]);
            return response()->json(['status' => 'failed', 'message' => 'Mutation Failed', 'error' => $e->getMessage()], 500);
        }
    }

    private function calculateTrend(array $history): string
    {
        if (count($history) < 2) return 'stable';

        $current = $history[0]['score'] ?? 0;
        $prev = $history[count($history)-1]['score'] ?? 0;

        if ($current > $prev + 5) return 'improving';
        if ($current < $prev - 5) return 'drifting';
        
        return 'stable';
    }
}
