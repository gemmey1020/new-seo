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
        return Cache::remember("site:{$site->id}:health:v1.3", self::CACHE_TTL, function () use ($site) {
            
            // 1. Gather Raw Metrics
            $metrics = $this->calculateMetrics($site);
            
            // 2. Calculate Score
            $score = $this->calculateScore($metrics);
            
            // 3. Build DTO
            $health = new HealthScore($score, $metrics);

            // v1.3 Hardening
            // We need Drift for Explainability
            $drift = $this->getDrift($site); // Use cached drift

            // 4. Calculate Confidence
            $confidence = $this->calculateConfidence($site);

            // 5. Generate Explanation
            $explanation = $this->generateExplanation($site, $score, $metrics, $drift, $confidence);

            // v1.2 History
            $history = $this->getHistory($site);
            
            $healthData = $health->toArray();
            $healthData['history'] = $history;
            
            // v1.3 Extensions
            $healthData['confidence'] = $confidence;
            $healthData['explanation'] = $explanation;

            return $healthData;
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
        return Cache::remember("site:{$site->id}:drift:v1.3", self::CACHE_TTL, function () use ($site) {
            
            $indicators = [
                'ghost' => $this->calculateGhostDrift($site),
                'zombie' => $this->calculateZombieRisk($site),
                'state' => $this->calculateStateDrift($site) // v1.2
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
        // Dimensions Check
        $stability = $this->calcStability($site);
        $compliance = $this->calcCompliance($site);
        $content = $this->calcContent($site); // Renamed from Metadata
        $structure = $this->calcStructure($site);

        return [
            'stability' => $stability,
            'compliance' => $compliance,
            'content' => $content, // New Key
            'structure' => $structure
        ];
    }

    private function calcStability(Site $site): array
    {
        // Reduced Weight: 0.3
        $lastRun = CrawlRun::where('site_id', $site->id)->latest()->first();
        if (!$lastRun) {
            return ['score' => 0, 'weight' => 0.3, 'metrics' => ['success_rate' => 0, 'latency_avg_ms' => 0]];
        }

        $logs = DB::table('crawl_logs')->where('crawl_run_id', $lastRun->id);
        $total = $logs->count();
        if ($total === 0) return ['score' => 0, 'weight' => 0.3, 'metrics' => ['success_rate' => 0, 'latency_avg_ms' => 0]];

        $success = $logs->where('status_code', 200)->count();
        $rate = $success / $total;

        $avgMs = $logs->avg('response_ms');
        
        $latencyScore = 0;
        if ($avgMs < 200) $latencyScore = 100;
        elseif ($avgMs < 500) $latencyScore = 90;
        elseif ($avgMs < 1000) $latencyScore = 70;
        elseif ($avgMs < 2000) $latencyScore = 50;
        
        $finalScore = round(($rate * 100 * 0.7) + ($latencyScore * 0.3));

        return [
            'score' => $finalScore,
            'weight' => 0.3,
            'metrics' => [
                'success_rate' => round($rate, 2),
                'latency_avg_ms' => round($avgMs)
            ]
        ];
    }

    private function calcCompliance(Site $site): array
    {
        // Reduced Weight: 0.2
        $critical = SeoAudit::where('site_id', $site->id)->where('status', 'open')->where('severity', 'critical')->count();
        $high = SeoAudit::where('site_id', $site->id)->where('status', 'open')->where('severity', 'high')->count();

        $penalty = ($critical * 5) + ($high * 2);
        $score = max(0, 100 - $penalty);

        return [
            'score' => $score,
            'weight' => 0.2,
            'metrics' => [
                'critical_audits' => $critical,
                'high_audits' => $high
            ]
        ];
    }

    private function calcContent(Site $site): array
    {
        // EXP-003: Content Quality (New Weight: 0.3)
        $totalPages = Page::where('site_id', $site->id)->count();
        if ($totalPages === 0) return ['score' => 0, 'weight' => 0.3, 'metrics' => ['meta_density' => 0, 'h1_density' => 0]];

        // 1. Meta Title Density
        $withMeta = DB::table('pages')
            ->join('seo_meta', 'pages.id', '=', 'seo_meta.page_id')
            ->where('pages.site_id', $site->id)
            ->whereNotNull('seo_meta.title')
            ->where('seo_meta.title', '!=', '')
            ->count();
        $metaRate = $withMeta / $totalPages;

        // 2. H1 Density (New Phase E Data)
        $withH1 = DB::table('pages')
            ->where('site_id', $site->id)
            ->where('h1_count', '>', 0)
            ->count();
        $h1Rate = $withH1 / $totalPages;

        // Composite Score: 60% Meta + 40% H1
        $score = round(($metaRate * 60) + ($h1Rate * 40));

        return [
            'score' => $score,
            'weight' => 0.3,
            'metrics' => [
                'meta_density' => round($metaRate, 2),
                'h1_density' => round($h1Rate, 2)
            ]
        ];
    }

    private function calcStructure(Site $site): array
    {
        // Increased Weight: 0.2
        $totalPages = Page::where('site_id', $site->id)->where('path', '!=', '/')->count();
        if ($totalPages === 0) return ['score' => 100, 'weight' => 0.2, 'metrics' => ['orphan_rate' => 0]];

        // Orphans (No Inbound)
        $orphans = Page::where('site_id', $site->id)
            ->where('path', '!=', '/')
            ->doesntHave('inboundLinks')
            ->count();
        
        $rate = $orphans / $totalPages;
        $score = max(0, 100 - ($rate * 100));

        return [
            'score' => round($score),
            'weight' => 0.2,
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

    private function calculateStateDrift(Site $site): array
    {
        // v1.2 State Drift: Pages with HTTP != 200
        $badState = Page::where('site_id', $site->id)->where('http_status_last', '!=', 200)->whereNotNull('http_status_last')->count();
        $total = Page::where('site_id', $site->id)->count();

        $severity = 'SAFE';
        if ($total > 0) {
            $ratio = $badState / $total;
            if ($ratio > 0.05) $severity = 'CRITICAL';
            elseif ($ratio > 0.01) $severity = 'DRIFTING';
        }

        return ['count' => $badState, 'severity' => $severity];
    }

    private function getHistory(Site $site): array
    {
        // v1.2 Trend History (Last 5 runs)
        // We re-calculate stability score for past runs
        // Optimized: In real system, we'd store "score" on CrawlRun. 
        // For v1.2 MVP, we calc on fly (expensive if logs huge, but ok for 5 runs)
        
        $runs = CrawlRun::where('site_id', $site->id)->latest()->take(5)->get();
        $history = [];
        
        foreach ($runs as $run) {
            $logs = DB::table('crawl_logs')->where('crawl_run_id', $run->id);
            $total = $logs->count();
            if ($total == 0) continue;
            
            $success = $logs->where('status_code', 200)->count();
            $avgMs = $logs->avg('response_ms') ?? 0;
            
            $rate = $success / $total;
            
            $latencyScore = 0;
            if ($avgMs < 200) $latencyScore = 100;
            elseif ($avgMs < 500) $latencyScore = 90;
            elseif ($avgMs < 1000) $latencyScore = 70;
            elseif ($avgMs < 2000) $latencyScore = 50;
            
            $score = round(($rate * 100 * 0.7) + ($latencyScore * 0.3));

            $history[] = [
                'run_id' => $run->id,
                'date' => $run->created_at->toIso8601String(),
                'score' => $score // Stability Score proxy
            ];
        }
        
        return $history;
    }

    // --- v1.3 Insight Hardening ---

    private function calculateConfidence(Site $site): array
    {
        // 1. Crawl Size Factor
        $lastRun = CrawlRun::where('site_id', $site->id)->latest()->first();
        $pagesCrawled = $lastRun ? $lastRun->pages_crawled : 0;
        $totalPages = Page::where('site_id', $site->id)->count();

        $crawlFactor = 0;
        if ($totalPages > 0) {
            $crawlFactor = min(1, $pagesCrawled / $totalPages);
        } else {
            // No pages known, assume 0 confidence if no crawl
            $crawlFactor = ($pagesCrawled > 0) ? 1 : 0; 
        }

        // 2. History Factor
        $runsCount = CrawlRun::where('site_id', $site->id)->count();
        $historyFactor = min(100, $runsCount * 20) / 100; // 5 runs = 1.0

        // 3. Score
        $score = (int) round(($crawlFactor * 50) + ($historyFactor * 50));

        // 4. Level & Reasons
        $level = 'LOW';
        $reasons = [];

        if ($score >= 80) $level = 'HIGH';
        elseif ($score >= 50) $level = 'MEDIUM';

        if ($crawlFactor < 0.5) $reasons[] = 'Small Sample Size (Coverage < 50%)';
        if ($historyFactor < 0.6) $reasons[] = 'Limited History (< 3 runs)';
        if ($score < 50) $reasons[] = 'Data Insufficient for Trust';

        return [
            'score' => $score,
            'level' => $level,
            'reasons' => $reasons
        ];
    }

    private function generateExplanation(Site $site, int $score, array $metrics, array $drift, array $confidence): array
    {
        $positive = [];
        $negative = [];
        $summary = '';

        // Stability
        $latency = $metrics['stability']['metrics']['latency_avg_ms'];
        if ($latency < 200) $positive[] = "Latency is excellent ({$latency}ms).";
        elseif ($latency > 1000) $negative[] = "High Latency detected ({$latency}ms).";

        $success = $metrics['stability']['metrics']['success_rate'];
        if ($success < 0.9) $negative[] = "Crawl stability issues (Success rate < 90%).";

        // Compliance
        $critical = $metrics['compliance']['metrics']['critical_audits'];
        if ($critical > 0) $negative[] = "{$critical} Critical Audits impacting score.";

        // Drift
        if (($drift['indicators']['ghost']['severity'] ?? 'SAFE') === 'CRITICAL') {
            $trend = $this->getDriftTrend($site, 'ghost');
            $negative[] = "Critical Ghost Drift detected (>10% 404s) ({$trend}).";
        }
        if (($drift['indicators']['state']['severity'] ?? 'SAFE') === 'CRITICAL') {
            $trend = $this->getDriftTrend($site, 'state');
            $negative[] = "Significant State Drift (Non-200 pages) ({$trend}).";
        }

        // Confidence
        if ($confidence['level'] === 'LOW') {
            $negative[] = "Confidence Low: " . implode(', ', $confidence['reasons']);
        }

        // Summary Construction
        if ($score >= 90) $summary = "Site is in excellent health.";
        elseif ($score >= 70) $summary = "Site is healthy but has room for optimization.";
        elseif ($score >= 50) $summary = "Site performance is degraded. Attention needed.";
        else $summary = "Critical issues detected. Immediate action required.";

        if (!empty($drift['indicators']['ghost']['severity']) && $drift['indicators']['ghost']['severity'] !== 'SAFE') {
            $summary .= " Drift detected.";
        }

        return [
            'positive' => $positive,
            'negative' => $negative,
            'summary' => $summary
        ];
    }

    private function getDriftTrend(Site $site, string $indicator): string
    {
        // Noise Detection: Check last 3 runs
        $runs = CrawlRun::where('site_id', $site->id)->latest()->take(3)->get();
        
        if ($runs->count() < 3) {
            return "Unknown/Limited History";
        }

        $unsafeCount = 0;

        foreach ($runs as $run) {
            $total = DB::table('crawl_logs')->where('crawl_run_id', $run->id)->count();
            if ($total == 0) continue;

            if ($indicator === 'ghost') {
                // Ghost: >= 400
                $count = DB::table('crawl_logs')->where('crawl_run_id', $run->id)->where('status_code', '>=', 400)->count();
                $ratio = $count / $total;
                // Threshold: > 0 is Unsafe (WARNING/CRITICAL)
                if ($ratio > 0) $unsafeCount++;

            } elseif ($indicator === 'state') {
                // State: != 200
                $count = DB::table('crawl_logs')->where('crawl_run_id', $run->id)->where('status_code', '!=', 200)->count();
                $ratio = $count / $total;
                // Threshold: > 0.01 is Unsafe (DRIFTING/CRITICAL)
                if ($ratio > 0.01) $unsafeCount++;
            }
        }

        return ($unsafeCount === 3) ? "Persistent" : "Transient";
    }
}
