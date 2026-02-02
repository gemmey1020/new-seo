<?php

namespace App\Services;

use App\DTO\Health\HealthScore;
use App\DTO\Health\DriftReport;
use App\DTO\Health\ReadinessVerdict;
use App\Models\Site\Site;
use App\Models\Crawl\CrawlRun;
use App\Models\Audit\SeoAudit;
use App\Models\Seo\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class HealthService
 * 
 * Implements the Health Contract (HC-001).
 * Read-Only Intelligence Layer.
 */
class HealthService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get the full Health Object for a site.
     * 
     * @param Site $site
     * @return array Matches HC-001 Schema
     */
    public function getHealth(Site $site): array
    {
        return Cache::remember("site:{$site->id}:health:v1.1", self::CACHE_TTL, function () use ($site) {
            
            // 1. Gather Raw Metrics
            $metrics = $this->calculateMetrics($site);
            
            // 2. Calculate Score
            $score = $this->calculateScore($metrics);
            
            // 3. Build DTO
            $health = new HealthScore($score, $metrics);
            
            return $health->toArray();
        });
    }

    /**
     * Get the Drift Report for a site.
     * 
     * @param Site $site
     * @return array Matches Drift Schema
     */
    public function getDrift(Site $site): array
    {
        return Cache::remember("site:{$site->id}:drift:v1.1", self::CACHE_TTL, function () use ($site) {
            
            $indicators = [
                'ghost' => $this->calculateGhostDrift($site),
                'zombie' => $this->calculateZombieRisk($site),
                'state' => ['count' => 0, 'severity' => 'SAFE'] // Placeholder for v1.2
            ];

            $drift = new DriftReport($indicators);
            return $drift->toArray();
        });
    }

    /**
     * Get Readiness Verdict.
     * 
     * @param Site $site
     * @return array Matches Readiness Schema
     */
    public function getReadiness(Site $site): array
    {
        $health = $this->getHealth($site);
        $drift = $this->getDrift($site);

        $blockers = [];
        $messages = [];

        // Check Health Stability
        if ($health['dimensions']['stability']['score'] < 70) {
            $blockers[] = 'Instability';
            $messages[] = 'Site stability is too low (<70).';
        }

        // Check Compliance
        if ($health['dimensions']['compliance']['metrics']['critical_audits'] > 10) {
            $blockers[] = 'Compliance_Failure';
            $messages[] = 'Too many critical audit issues.';
        }

        // Check Drift
        if (($drift['indicators']['ghost']['severity'] ?? 'SAFE') === 'CRITICAL') {
            $blockers[] = 'DRIFT_CRITICAL_GHOST';
            $messages[] = 'Critical Ghost Drift detected.';
        }
        
        $ready = empty($blockers);
        $msg = $ready ? 'Site is Ready for Authority.' : implode(' ', $messages);

        return (new ReadinessVerdict($ready, $blockers, $msg))->toArray();
    }

    // --- Internal Calculation Logic (EXP-003) ---

    private function calculateMetrics(Site $site): array
    {
        // Dimensions
        $stability = $this->calcStability($site);
        $compliance = $this->calcCompliance($site);
        $metadata = $this->calcMetadata($site);
        $structure = $this->calcStructure($site);

        return [
            'stability' => $stability,
            'compliance' => $compliance,
            'metadata' => $metadata,
            'structure' => $structure
        ];
    }

    private function calcStability(Site $site): array
    {
        $lastRun = CrawlRun::where('site_id', $site->id)->latest()->first();
        if (!$lastRun) {
            return ['score' => 0, 'weight' => 0.4, 'metrics' => ['success_rate' => 0, 'latency_avg_ms' => 0]];
        }

        // EXP-003: Success Rate
        $logs = DB::table('crawl_logs')->where('crawl_run_id', $lastRun->id);
        $total = $logs->count();
        if ($total === 0) return ['score' => 0, 'weight' => 0.4, 'metrics' => ['success_rate' => 0, 'latency_avg_ms' => 0]];

        $success = $logs->where('status_code', 200)->count();
        $rate = $success / $total;

        // EXP-003: Latency
        $avgMs = $logs->avg('response_ms');

        // Normalize Score: Rate * 100. Penalize latency > 500ms? 
        // Simple: just rate * 100 for v1.1
        $score = round($rate * 100); 

        return [
            'score' => $score,
            'weight' => 0.4,
            'metrics' => [
                'success_rate' => round($rate, 2),
                'latency_avg_ms' => round($avgMs)
            ]
        ];
    }

    private function calcCompliance(Site $site): array
    {
        // EXP-003: Audit Score
        $critical = SeoAudit::where('site_id', $site->id)->where('status', 'open')->where('severity', 'critical')->count();
        $high = SeoAudit::where('site_id', $site->id)->where('status', 'open')->where('severity', 'high')->count();

        $penalty = ($critical * 5) + ($high * 2);
        $score = max(0, 100 - $penalty);

        return [
            'score' => $score,
            'weight' => 0.3,
            'metrics' => [
                'critical_audits' => $critical,
                'high_audits' => $high
            ]
        ];
    }

    private function calcMetadata(Site $site): array
    {
        // EXP-003: Density
        $totalPages = Page::where('site_id', $site->id)->count();
        if ($totalPages === 0) return ['score' => 0, 'weight' => 0.2, 'metrics' => ['density_rate' => 0]];

        $withMeta = DB::table('pages')
            ->join('seo_meta', 'pages.id', '=', 'seo_meta.page_id')
            ->where('pages.site_id', $site->id)
            ->whereNotNull('seo_meta.title')
            ->where('seo_meta.title', '!=', '')
            ->count();

        $rate = $withMeta / $totalPages;
        
        return [
            'score' => round($rate * 100),
            'weight' => 0.2,
            'metrics' => [
                'density_rate' => round($rate, 2)
            ]
        ];
    }

    private function calcStructure(Site $site): array
    {
        // EXP-003: Orphan Rate
        $totalPages = Page::where('site_id', $site->id)->where('path', '!=', '/')->count();
        if ($totalPages === 0) return ['score' => 100, 'weight' => 0.1, 'metrics' => ['orphan_rate' => 0]];

        // Pages with NO inbound links
        $orphans = Page::where('site_id', $site->id)
            ->where('path', '!=', '/')
            ->doesntHave('inboundLinks')
            ->count();
        
        $rate = $orphans / $totalPages;
        $score = max(0, 100 - ($rate * 100)); // 100% orphans = 0 score.

        return [
            'score' => round($score),
            'weight' => 0.1,
            'metrics' => [
                'orphan_rate' => round($rate, 2)
            ]
        ];
    }

    private function calculateScore(array $metrics): int
    {
        $total = 0;
        foreach ($metrics as $d) {
            $total += $d['score'] * $d['weight'];
        }
        return round($total);
    }

    private function calculateGhostDrift(Site $site): array
    {
        // EXP-003: Ghost (HTTP >= 400)
        $count = Page::where('site_id', $site->id)->where('http_status_last', '>=', 400)->count();
        $total = Page::where('site_id', $site->id)->count();
        
        $severity = 'SAFE';
        if ($total > 0) {
            $ratio = $count / $total;
            if ($ratio > 0.10) $severity = 'CRITICAL'; // > 10%
            elseif ($ratio > 0) $severity = 'WARNING';
        }

        return ['count' => $count, 'severity' => $severity];
    }

    private function calculateZombieRisk(Site $site): array
    {
        // EXP-003: Zombie Risk (Orphans)
        $orphans = Page::where('site_id', $site->id)
            ->where('path', '!=', '/')
            ->doesntHave('inboundLinks')
            ->count();
        
        $total = Page::where('site_id', $site->id)->count();

        $severity = 'SAFE';
        if ($total > 0) {
            $ratio = $orphans / $total;
            if ($ratio > 0.05) $severity = 'WARNING'; 
        }

        return ['count' => $orphans, 'severity' => $severity];
    }
}
