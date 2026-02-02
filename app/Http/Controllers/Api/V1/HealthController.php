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
        return response()->json($data);
    }
}
