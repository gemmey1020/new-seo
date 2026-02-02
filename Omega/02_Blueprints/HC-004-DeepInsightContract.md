# HC-004: Deep Insight Contract (v1.5)

**Status:** DRAFT
**Scope:** Content Analysis and Deep Structure
**Adherence:** Strict Read-Only

## 1. CONTENT ANALYSIS OBJECT
Returned by `ContentService`.

```json
{
  "readability": {
    "score": 65.5, // 0-100
    "grade_level": "8th Grade",
    "metrics": {
      "word_count": 1200,
      "sentence_count": 80,
      "avg_sentence_length": 15
    }
  },
  "structure": {
    "h1_count": 1,
    "h_structure": ["h1", "h2", "h2", "h3"],
    "issues": ["H3 follows H1 directly (Skipped H2)"]
  },
  "keywords": {
    "detected": [
      {"term": "seo", "count": 15, "density": 1.2},
      {"term": "laravel", "count": 8, "density": 0.6}
    ]
  },
  "generated_at": "2026-02-02T12:00:00Z"
}
```

## 2. DEEP AUDIT PAYLOADS
Extensions to existing `SeoAudit` `data` json column.

### Canonical Mismatch
```json
{
  "audit_type": "canonical_mismatch",
  "details": {
    "current_url": "https://example.com/page-a",
    "canonical_tag": "https://example.com/page-b",
    "match": false
  }
}
```

### Schema Validation
```json
{
  "audit_type": "schema_error",
  "details": {
    "error_message": "Unexpected token }",
    "snippet": "{ \"@context\": ... }"
  }
}
```

## 3. CONSTRAINTS
*   **On-Demand:** These heavy objects are NOT included in the lightweight `HealthScore` (HC-001).
*   **Aggregation:** `HealthService` may *reference* the existence of these issues (via audit counts) but should not embed the full analysis.
