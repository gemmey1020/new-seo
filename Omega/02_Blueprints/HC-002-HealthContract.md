# HC-002: Health Contract (v1.2)

**Status:** APPROVED
**Base:** HC-001 (v1.1)
**Reason:** v1.2 Insight Refinement

## 1. Overview
Extends HC-001 to include deeper insights without breaking read-only guarantees.

## 2. Invariants (Inherited)
*   Read-Only
*   Deterministic
*   Cached

## 3. Extensions to HC-001

### A. Stability Logic (Latency Penalty)
**New Logic:**
`Score = (Success Rate * 70%) + (Latency Score * 30%)`

**Latency Scoring:**
*   < 200ms: 100
*   < 500ms: 90
*   < 1000ms: 70
*   < 2000ms: 50
*   > 2000ms: 0

### B. Health Object (Additions)
```json
{
  ...
  "history": [ // NEW
    { "run_id": 101, "date": "2026-02-01T10:00:00", "score": 85 },
    { "run_id": 102, "date": "2026-02-02T10:00:00", "score": 82 }
  ]
}
```

### C. Drift Object (Additions)
```json
{
  "indicators": {
    ...
    "state": { // NEW
      "count": 12, // Number of pages with http_status != 200
      "severity": "DRIFTING" // > 5% is CRITICAL
    }
  }
}
```

## 4. Backwards Compatibility
*   All HC-001 fields must remain.
*   New fields are additive.
