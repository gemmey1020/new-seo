# BP-002: Insight Refinement (v1.2)

**Status:** DRAFT
**Context:** v1.2 Accuracy Improvement
**Scope:** Read-Only Refinements (No Schema Changes)

## 1. Objectives
Refine the Intelligence Layer to provide deeper insights without operational changes.

## 2. Refinement Logic

### A. State Drift (Sitemap vs Reality)
*   **Definition:** The discrepancy between what the sitemap *claims* (implicitly 200 OK) and what the crawler *finds*.
*   **Proxy Logic (v1 Limitations):** Since we don't store "Sitemap Status", we assume any URL discovered via Sitemap (`pages.created_at` approx `import_job_at`?) *should* be 200.
*   **Metric:** Count of pages where `http_status_last != 200` AND `http_status_last` is not NULL.
*   **Severity:** 
    *   `SAFE` if ratio < 1%
    *   `DRIFTING` if ratio < 5%
    *   `CRITICAL` if ratio > 5%

### B. Latency Penalties (Stability Score)
*   **Current:** `Success Rate * 100` (Ignores Latency)
*   **Refined:** `(Success Rate * 70) + (Latency Score * 30)`
*   **Latency Score Func:**
    *   < 200ms = 100
    *   < 500ms = 90
    *   < 1000ms = 70
    *   < 2000ms = 50
    *   > 2000ms = 0

### C. Trend History (Stability)
*   **Definition:** Analyzing the last 5 `crawl_runs` to detect degradation.
*   **Logic:**
    *   Fetch last 5 runs.
    *   Calc Stability Score for each.
    *   Return array of `{ run_id, date, score }`.
*   **Output:** Added to `HealthObject` under `history`.

## 3. Schema Updates (HC-001 Extension)

### Health Object (Additions)
```json
{
  ...
  "history": [
    { "date": "2026-02-01T10:00:00", "score": 85 },
    { "date": "2026-02-02T10:00:00", "score": 82 }
  ]
}
```

### Drift Object (Refinement)
```json
{
  "indicators": {
    "state": {
      "count": 12, // Non-200 pages
      "severity": "DRIFTING"
    }
    ...
  }
}
```

## 4. Implementation Plan
1.  Update `HealthService::calcStability` to include latency.
2.  Update `HealthService::getDrift` to implement `state` logic.
3.  Add `HealthService::getHistory` method and merge into response.
