<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site\Site;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    private HealthService $healthService;

    public function __construct(HealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    /**
     * Get the full Health Object.
     * GET /api/v1/sites/{site}/health
     */
    public function show(Site $site): JsonResponse
    {
        $this->authorize('view', $site); // Enforce Policy
        
        $data = $this->healthService->getHealth($site);

        // PRESENTATION LOCK: Downgrade severity if pre-policy
        if (config('seo.phase') === 'CORE_FROZEN_NO_POLICY') {
            // Downgrade Grade F/D to Informational if based on policy drift
            // Since we don't have policies yet, low scores are "structural" not "violations"
            $data['grade_context'] = 'PRE_POLICY';
            
            // Downgrade confidence
            if ($data['confidence']['level'] !== 'HIGH') {
                $data['confidence']['level'] = 'PRE_POLICY';
                $data['confidence']['reasons'][] = 'Confidence scoring awaits Policy Layer activation.';
            }
        }

        return response()->json($data);
    }

    /**
     * Get Drift Report.
     * GET /api/v1/sites/{site}/health/drift
     */
    public function drift(Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $data = $this->healthService->getDrift($site);

        // PRESENTATION LOCK: Suppress Critical Drift
        if (config('seo.phase') === 'CORE_FROZEN_NO_POLICY') {
            if ($data['status'] === 'CRITICAL' || $data['status'] === 'DRIFTING') {
                $data['original_status'] = $data['status'];
                $data['status'] = 'INFORMATIONAL';
                $data['notes'] = 'Structural indicators present. Policy evaluation pending.';
            }
        }

        return response()->json($data);
    }

    /**
     * Get Readiness Verdict.
     * GET /api/v1/sites/{site}/health/readiness
     */
    public function readiness(Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $data = $this->healthService->getReadiness($site);

        // PRESENTATION LOCK: Suppress Readiness Blocking
        if (config('seo.phase') === 'CORE_FROZEN_NO_POLICY') {
            if (!$data['ready']) {
                $data['state'] = 'INFORMATIONAL';
                $data['message'] = 'Readiness enforcement not active in current phase';
                // We keep 'ready' => false to be truthful, but UI should handle 'state'
            }
        }

        return response()->json($data);
    }
}
