# HC-001: Health Contract (v1.1)

**Status:** APPROVED
**Type:** API & Service Contract
**Context:** v1.1 Intelligence Layer

## 1. Overview
This document defines the strict schema and behavioral invariants for the Site Health Intelligence Layer. All `HealthService` implementations must adhere to this output format.

## 2. Invariants
1.  **Read-Only:** Calculation of health MUST NOT mutate any database state.
2.  **Deterministic:** Given the same database snapshot, the score MUST be identical.
3.  **Fast:** Calculation target < 100ms. Heavy aggregation should be cached.
4.  **No External Dependencies:** Health is derived SOLELY from internal models (`pages`, `logs`, `audits`).

## 3. Health Object Schema

```json
{
  "score": 85, // integer 0-100
  "grade": "B", // string (A, B, C, D, F)
  "generated_at": "ISO-8601",
  "dimensions": {
    "stability": {
      "score": 90,
      "weight": 0.4,
      "metrics": {
        "success_rate": 0.95, // logs: 200 OK / total
        "latency_avg_ms": 250 // logs: avg response_ms
      }
    },
    "compliance": {
      "score": 80,
      "weight": 0.3,
      "metrics": {
        "critical_audits": 2, // count(severity='critical')
        "high_audits": 0 // count(severity='high')
      }
    },
    "metadata": {
      "score": 100,
      "weight": 0.2,
      "metrics": {
        "density_rate": 1.0 // pages with title/desc / total pages
      }
    },
    "structure": {
      "score": 100,
      "weight": 0.1,
      "metrics": {
        "orphan_rate": 0.0 // pages with 0 inbound links / total pages
      }
    }
  }
}
```

## 4. Drift Object Schema

```json
{
  "status": "DRIFTING", // SAFE, DRIFTING, CRITICAL
  "indicators": {
    "ghost": {
      "count": 4, // Pages in DB but 404/Dead
      "severity": "CRITICAL" // > 0 is Warning, > 10% is Critical
    },
    "zombie": {
      "count": 0, // Pages Found but Orphaned/Unlinked
      "severity": "SAFE" 
    },
    "state": {
      "count": 0, // Sitemap Status != Crawl Status
      "severity": "SAFE"
    }
  }
}
```

## 5. Readiness Verdict

The `verdict` object determines if v2 (Active Authority) *could* be enabled.

```json
{
  "ready": false,
  "blockers": [
    "DRIFT_CRITICAL_GHOST"
  ],
  "message": "Site has 4 Ghost Pages (404s). Fix crawling errors before enabling automation."
}
```

### Blocking Rules
| Flag | Trigger |
| :--- | :--- |
| **DRIFT_CRITICAL_GHOST** | Ghost Count > 1% of Total Pages |
| **DRIFT_CRITICAL_ZOMBIE** | Zombie Count > 5% of Total Pages |
| **Instability** | Stability Score < 70 |
| **Compliance_Failure** | Critical Audits > 10 |

## 6. Implementation Notes
*   **Source:** All logic maps directly to EXP-003 SQL queries.
*   **Caching:** Results should be cached for 5-10 minutes to prevent DB thrashing on dashboard refreshes.
