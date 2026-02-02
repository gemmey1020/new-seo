# DEPLOYMENT RELEASE PLAN: v1.3 Intelligence Layer

**Role:** Production Readiness Engineer
**Target:** v1.3 Intelligence Layer (Passive Observer)
**Status:** READY FOR SOAK
**Date:** 2026-02-02

## 1. RUNTIME MONITORING CHECKLIST
*   [ ] **Host Health:** Monitor CPU/RAM usage during peak Crawl + Health Check cycles.
*   [ ] **Database Load:** Monitor `crawl_logs` read I/O.
    *   *Risk:* `getDriftTrend` aggregates last 3 runs. Ensure indices on `crawl_run_id` are active.
*   [ ] **API Latency:** Track `GET /api/v1/sites/{site}/health` response times.
*   [ ] **Error Rate:** Watch `laravel.log` for 500 errors originating from `HealthService`.

## 2. PERFORMANCE BUDGET
**Strict Latency Targets:**
*   **Cached Response (Hit):** < 50ms (p95)
*   **Uncached Calculation (Miss):** < 300ms (p95)
*   **Degraded State:** Any response > 500ms is considered a performance regression.

**Resource Limits:**
*   **Memory:** `HealthService` should not exceed 10MB per request.
*   **DB Queries:** Max 15 queries per Uncached Request (N+1 strict audit).

## 3. LOGGING & VERIFICATION
*   **Access Logs:** Verify `200 OK` on health endpoints.
*   **Application Logs:** usage of `App\Services\HealthService`.
*   **Drift Logs:** While read-only, critical drift findings should be logged to application info log for debugging (Code update not required, observable via API output).

## 4. CACHE VALIDATION (Redis/File)
*   **Key Pattern:** `site:{id}:health:v1.3`
*   **TTL Verification:** Ensure items expire after 300s (5m).
*   **Invalidation:** Confirm new `CrawlRun` completion *does not* auto-clear cache in v1.3 (Passive), meaning up to 5m delay is acceptable.
*   **Hit/Miss Ratio:** Target > 80% Hit Rate.

## 5. SOAK METRICS (Observation Phase)
**Duration:** Minimum 7 Days

### A. False Positive Drift
*   **Metric:** Count of "Persistent" Drift alerts that are actually valid pages.
*   **Method:** Manual audit of 10 random "Critical" findings per day.
*   **Threshold:** 0 Allowed.

### B. Confidence Evolution
*   **Metric:** Monitor `confidence.score` growth over 5 crawl runs.
*   **Expectation:** Score should monotonically increase to 100 as history builds.
*   **Validation:** If score matches `(runs * 10) + (coverage * 50)`, logic holds.

### C. Trend Stability
*   **Metric:** Oscillation count.
*   **Drift:** If `Ghost Drift` toggles SAFE <-> CRITICAL every run, "Persistent" logic is failing.

## 6. ROLLBACK CONDITIONS
Although v1.3 is Read-Only, rollback logic applies to **Deployment configuration**:
1.  **High Latency:** If p95 > 500ms, disable API route in `routes/api.php`.
2.  **DB Lock Contention:** If `crawl_logs` reads block writes, revert to v1.2 (remove trend query).
3.  **Security:** If Endpoint exposed without Auth (Sanctum failure), Immediate Block.

## 7. EXIT CRITERIA (Gate to v2)
**Authorized to proceed to v2 (Active Authority) ONLY when:**
1.  ✅ **Uptime:** 7 Days continuous operation.
2.  ✅ **Data Depth:** 3+ Sites with > 5 Crawl Runs (100% History Factor).
3.  ✅ **Accuracy:** ZERO confirmed False Positive Critical Drifts.
4.  ✅ **Performance:** Latency targets met (p95 < 50ms).
5.  ✅ **User Trust:** User explicitly approves "Explainability" utility.

---
**Engineer Sign-off:**
Antigravity
