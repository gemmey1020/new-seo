# OBSERVATION_MODE_GUIDE

**Document Type:** Developer Guide
**Target Audience:** Frontend & Backend Developers
**Last Updated:** 2026-02-05 (Phase J.2.1)
**Status:** ACTIVE

---

## 1. DEFINITION OF OBSERVATION MODE

**Observation Mode** is the strictly enforced operational state of the Policy Engine where:

1. **Read-Only:** The system performs ZERO state mutations (database/cache writes) based on policy evaluation.
2. **Non-Authoritative:** The system provides diagnostic **evidence** but takes NO automated action (blocking, fixing, hiding).
3. **Human-Gated:** All verdicts (`PASS`, `FAIL`, `WARN`) are labels for human interpretation, not machine triggers.

In this mode, the Policy Engine acts as a **mirror**, reflecting the state of the content without attempting to change it.

---

## 2. AUTHORITY BOUNDARY: VERDICT VS. EVIDENCE

It is critical to distinguish between the **Authoritative Verdict** and **Observational Evidence**.

### 2.1 The Authoritative Verdict

The **Verdict** is the final, computed judgment of the Policy Engine. It is the **ONLY** field that should be used to determine status.

* **Source:** `PolicyEvaluator` logic.
* **Field:** `status` (`PASS`, `FAIL`, `WARN`).
* **Usage:** UI badging, summary counts, filtering.

### 2.2 Observational Evidence

**Evidence** is supplementary metadata describing *why* a verdict was reached. It is **Non-Authoritative**.

* **Source:** `PolicyRuleSet` enrichment.
* **Fields:** `measured_value`, `expected_value`, `comparison`, `confidence`.
* **Usage:** User context, progressive disclosure details.

### 2.3 The Boundary Rule

> **⛔ CRITICAL RULE:**
> NEVER use Evidence fields to derive, override, or re-calculate a Verdict.

**Why?**
Logic thresholds evolve. If the UI calculates `if (measured < 10)` and the backend changes to `8`, the UI will drift and show incorrect status. Always trust the backend `status`.

---

## 3. RULES FOR UI USAGE

### Rule 1: Visual Hierarchy

Evidence should always be secondary to the verdict.

* **Verdict:** Primary, Bold, Colored (Red/Orange/Green).
* **Evidence:** Secondary, Muted, Standard Text.

### Rule 2: Progressive Disclosure

Evidence should not clutter the primary view.

* **Default:** Hidden.
* **Interaction:** Revealed on hover or expand.

### Rule 3: Null Safety

Evidence is optional. The UI MUST handle `null` gracefully.

**Example 1: Evidence Present (Title Length)**

```json
{
  "status": "FAIL",
  "explanation": "Title too short",
  "measured_value": 9,
  "expected_value": "10-60 chars"
}
```

*UI:* Render Verdict. On expand: "Current: 9 | Expected: 10-60 chars"

**Example 2: Evidence Null (Meta Description)**

```json
{
  "status": "FAIL",
  "explanation": "Missing Description",
  "measured_value": null
}
```

*UI:* Render Verdict. On expand: Show explanation only. DO NOT render "Current: null".

---

## 4. FORBIDDEN PATTERNS

❌ **Client-Side Thresholding**

```javascript
// FORBIDDEN
const isFail = violation.measured_value < 10; // DANGEROUS!
```

❌ **Evidence Parsing**

```javascript
// FORBIDDEN
const max = parseInt(violation.expected_value.split('-')[1]); // BRITTLE!
```

❌ **Automated Action**

```javascript
// FORBIDDEN
if (violation.confidence === 'high') { autoFix(); } // UNAUTHORIZED!
```

---

## 5. ROLLBACK PROCEDURE

If Observation Mode is compromised (e.g., unintended writes or logic drift), execute the Phase J.2.1 Rollback:

1. **Revert Code:**

    ```bash
    git checkout HEAD~1 app/Services/Policy/PolicyRuleSet.php
    git checkout HEAD~1 app/Services/Policy/PolicyEvaluator.php
    ```

2. **Clear State:**

    ```bash
    php artisan cache:clear
    ```

3. **Verify:**
    Run `php artisan test --filter=Policy` to confirm clean state.

---

**Certified By:** Verification & Documentation Authority
**Phase:** J.2.2 (Verification)
