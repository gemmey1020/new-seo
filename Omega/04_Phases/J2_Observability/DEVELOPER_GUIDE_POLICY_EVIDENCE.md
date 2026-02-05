# DEVELOPER_GUIDE_POLICY_EVIDENCE

**Schema Version:** v1.1
**Phase:** J.2.1 (Observability Enrichment)
**Status:** FROZEN

> ⚠️ **IMPORTANT:** Evidence fields defined in this guide are **NON-AUTHORITATIVE**. They are for display and diagnostics only. Never use them for automated decision-making.

---

## 1. VIOLATION SCHEMA v1.1

The `PolicyEvaluator` returns violations with the following structure. New fields added in J.2.1 are marked *[Optional]*.

```json
{
  "policy_code": "String (e.g., 'CONTENT_TITLE_LENGTH')",
  "severity": "String ('CRITICAL', 'HIGH', 'WARNING', 'OPTIMIZATION', 'ADVISORY')",
  "status": "String ('FAIL', 'WARN', 'PASS')",
  "explanation": "String (Human-readable reason)",
  
  // EVIDENCE METADATA (J.2.1)
  "measured_value": "Mixed [Optional] (The actual observed value)",
  "expected_value": "String [Optional] (Human-readable threshold)",
  "comparison": "String [Optional] (Semantic operator)",
  "confidence": "String ('high', 'medium', 'low')"
}
```

### Field Definitions

| Field | Type | Description | Example |
|:------|:-----|:------------|:--------|
| `measured_value` | Mixed | Raw value triggering the rule. Can be int, string, or boolean. Null if N/A. | `9`, `404`, `0` |
| `expected_value` | String | Descriptive threshold. **NOT** machine-parseable. | `"10-60 chars"`, `"200 OK"` |
| `comparison` | String | Semantic relationship between measured and expected. | `"below_minimum"`, `"not_equal"` |
| `confidence` | String | Reliability of the measurement. | `"high"` |

---

## 2. CONFIDENCE LEVELS

Confidence indicates the certainty of the *measurement*, not the severity of the *impact*.

* **HIGH:** Deterministic, machine-verifiable. (e.g., HTTP Status, Character Count). most rules are High.
* **MEDIUM:** Heuristic or complex parsing. (e.g., Content Relevance, Sentiment).
* **LOW:** Best-guess or probabilistic. (e.g., Author Intent).

**Usage:**
Reliability display. A "Low" confidence violation might optionally be hidden or flagged as "Potential" in the UI.

---

## 3. FORBIDDEN IMPLEMENTATION PATTERNS

To preserve the **Authority Invariant**, the following patterns are strictly prohibited in client code.

### ⛔ Pattern A: Shadow Logic

Re-implementing rule logic in the frontend.

*Bad:*

```javascript
if (violation.measured_value > 60) { showRedBadge(); }
```

*Good:*

```javascript
if (violation.status === 'FAIL') { showRedBadge(); }
```

### ⛔ Pattern B: String Parsing

Attempting to regex strict values from `expected_value`.

*Bad:*

```javascript
const max = parseInt(violation.expected_value);
```

*Why:* `expected_value` is localized text ("10-60 characters"). It will break your parser.

### ⛔ Pattern C: Null Assumption

Assuming evidence always exists.

*Bad:*

```javascript
render(violation.measured_value.toString());
```

*Why:* `measured_value` is null for binary rules (e.g., missing meta tag). This will crash.

---

## 4. UI IMPLEMENTATION CHECKLIST

* [ ] Check `if (measured_value !== null)` before rendering evidence block.
* [ ] Use `explanation` as the primary text.
* [ ] Render `expected_value` as a hint/subtitle.
* [ ] Do **not** color-code based on evidence usage; use `severity` instead.

---

**Guide Owner:** Architecture Team
**Last Revised:** 2026-02-05
