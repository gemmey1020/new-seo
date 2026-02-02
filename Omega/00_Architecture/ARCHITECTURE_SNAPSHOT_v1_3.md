# ARCHITECTURE SNAPSHOT v1.3

**Layer:** Intelligence (Passive)
**Scope:** `app/Services/HealthService.php`

## 1. COMPONENT HIERARCHY

### Core Service
*   **HealthService:** Aggregator of all Intelligence logic.
    *   `getHealth(Site)`: Primary Entry Point.
    *   `getDrift(Site)`: Deviation Monitor.
    *   `getReadiness(Site)`: Gatekeeper Logic.

### Data Contracts (DTOs)
*   `HealthScore`: { score, grade, dimensions: [stability, compliance, metadata, structure] }
*   `DriftReport`: { status, indicators: [ghost, zombie, state] }
*   `ReadinessVerdict`: { ready (bool), blockers [], message }

### Logic Flows
1.  **Metric Calculation:** Raw SQL aggregates from `crawl_logs` and `seo_audits`.
2.  **Scoring Engine:** Weighted algorithm (Stability 40%, Compliance 30%, Meta 20%, Structure 10%).
3.  **Confidence Engine:** `(Crawl_Size * 50) + (History_Depth * 50)`.
4.  **Explainability Engine:** Deterministic NL generation based on threshold buckets.
5.  **Trend Engine (v1.3 Patch):** `getDriftTrend` (Last 3 Runs) -> Persistent/Transient classification.

## 2. DATA FLOW
*   **Input:** Read-Only access to `pages`, `crawl_runs`, `crawl_logs`, `seo_audits`.
*   **Process:** On-demand calculation + Caching (TTL 5min).
*   **Output:** JSON Objects via API Controllers (`HealthController`).
*   **Storage:** `cache_store` (Redis/File). No persistent DB writes by this layer.

## 3. DEPENDENCIES
*   **Upstream:** `CrawlService` (must produce logs).
*   **Downstream:** API Consumers (Dashboard).

## 4. INVARIANTS
*   **Idempotency:** Repeated calls yield identical results (for same DB state).
*   **Isolation:** Failure in HealthService does not affect Site availability.
