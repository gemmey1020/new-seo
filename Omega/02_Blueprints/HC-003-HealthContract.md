# HC-003: Health Contract (v1.3)

**Status:** APPROVED
**Base:** HC-002 (v1.2)
**Reason:** v1.3 Insight Hardening

## 1. Overview
Extends HC-002 to include Confidence Scores and Natural Language Explainability.

## 2. Invariants (Inherited)
*   Read-Only (Strict)
*   Deterministic

## 3. Extensions to HC-002

### A. Confidence Object
Must be included in root Health response.
```json
"confidence": {
  "score": 100,
  "level": "HIGH", // >80 HIGH, >50 MEDIUM, <50 LOW
  "reasons": [] // Array of strings if score < 100
}
```

### B. Explanation Object
Must be included in root Health response.
```json
"explanation": {
  "positive": ["Latency is excellent (<200ms)"],
  "negative": ["Critical Drift detected (>5% 404s)"],
  "summary": "Site is stable but suffering from content drift."
}
```

## 4. Backwards Compatibility
*   All HC-002 fields remain.
*   Implementation must populate new fields without zero-values where possible.
