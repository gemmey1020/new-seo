# OBSERVATION_MODE_GUIDE

**Document Type:** Developer Guide  
**Target Audience:** Frontend & Backend Developers  
**Last Updated:** 2026-02-05 (Phase J.2.1)

---

## WHAT IS OBSERVATION MODE?

**Observation Mode** is the operational state where the Policy Engine evaluates pages against SEO rules but **does not enforce** any actions. The system provides diagnostic information to humans without automated intervention.

### Key Characteristics

1. **Non-Authoritative:** The system observes and reports; it does not block, fix, or modify content.
2. **Read-Only:** Zero database writes beyond normal crawl execution.
3. **Human-Facing:** All verdicts are for human decision-making, not automation.

---

## CORE PRINCIPLE: POLICY vs. EVIDENCE

### Policy Verdict (Authoritative)

**What It Is:** The final judgment on whether a page passes or fails a rule.

**Determined By:** `PolicyEvaluator` backend service  
**Output Fields:**

- `status`: `PASS` | `FAIL` | `WARN`
- `severity`: `CRITICAL` | `HIGH` | `WARNING` | `OPTIMIZATION` | `ADVISORY`
- `explanation`: Human-readable reason

**Example:**

```json
{
  "policy_code": "CONTENT_TITLE_LENGTH",
  "status": "FAIL",
  "severity": "WARNING",
  "explanation": "Title is too short (< 10 chars)."
}
```

### Evidence Metadata (Observational)

**What It Is:** Supplementary data showing the measured value that triggered the violation.

**Determined By:** `PolicyRuleSet` enrichment (Phase J.2.1)  
**Output Fields:**

- `measured_value`: Actual observed value (e.g., `9` for title length)
- `expected_value`: Human-readable threshold (e.g., `"10-60 characters"`)
- `comparison`: Semantic operator (e.g., `"below_minimum"`)
- `confidence`: Certainty level (e.g., `"high"`)

**Example:**

```json
{
  "measured_value": 9,
  "expected_value": "10-60 characters",
  "comparison": "below_minimum",
  "confidence": "high"
}
```

---

## CRITICAL DISTINCTION

### ✅ Correct Understanding

**Policy verdict determines outcome.**  
Evidence explains *why* the verdict occurred.

```
Verdict: FAIL (Title too short)
Evidence: Current length is 9 characters, expected 10-60.
```

The verdict (`FAIL`) is authoritative.  
The evidence (`9`) is explanatory.

### ❌ Incorrect Understanding

**Evidence determines outcome.**  
Policy verdict is just a label.

```
User sees: "Current: 9, Expected: 10-60"
User logic: "9 < 10, so FAIL"
```

**WHY THIS IS WRONG:**  
If evidence and verdict logic drift (bug, update), the user-derived verdict will be incorrect.

---

## SAFE INTERPRETATION RULES

### Rule 1: Trust the Verdict, Not the Evidence

**Correct:**

```javascript
if (violation.status === 'FAIL') {
    alertUser('This page needs attention');
}
```

**Incorrect:**

```javascript
if (violation.measured_value < 10) {
    alertUser('This page needs attention');
}
```

**Rationale:** The verdict is computed server-side with threshold logic. Evidence is for human understanding, not programmatic decisions.

---

### Rule 2: Evidence May Be Null

Not all rules provide evidence. Some violations are binary (yes/no) without measurable values.

**Example:** `STRUCTURE_ORPHAN` (Page is/isn't orphaned)

**Correct:**

```javascript
if (violation.measured_value !== undefined) {
    displayEvidence(violation);
} else {
    displayVerdict Only(violation);
}
```

**Incorrect:**

```javascript
displayEvidence(violation);  // Will fail if measured_value is null
```

**Rationale:** Non-enriched rules return `null` evidence fields. UI must degrade gracefully.

---

### Rule 3: Evidence Is Secondary Information

Evidence should be visually de-emphasized (smaller font, muted color, hidden by default).

**Correct UI Hierarchy:**

```
❌ Title is too short (< 10 chars).     [Primary, bold]
   Affects: Page Title                  [Secondary, normal weight]
   Current: 9 | Expected: 10-60         [Tertiary, muted]
```

**Incorrect UI Hierarchy:**

```
Current: 9 | Expected: 10-60            [Primary, bold]
❌ Title is too short                   [Secondary]
```

**Rationale:** The *verdict* is the actionable information. Evidence is context.

---

## BACKEND DEVELOPERS: EVIDENCE EMISSION RULES

### When to Add Evidence

✅ **Add evidence if:**

- The rule has a measurable threshold (e.g., length, count, numeric status)
- The measured value provides diagnostic clarity
- The value is deterministic (not probabilistic)

❌ **Do NOT add evidence if:**

- The rule is binary (exists/doesn't exist)
- The measured value is complex (requires parsing)
- The value could mislead users (e.g., partial data)

### Example: Good Evidence

```php
// CONTENT_TITLE_LENGTH
if ($len < 10) {
    return self::violation(
        self::SEVERITY_WARNING,
        'Title is too short (< 10 chars).',
        $len,             // Clear measured value
        '10-60 characters', // Clear threshold
        'below_minimum',  // Semantic operator
        'high'            // Deterministic
    );
}
```

### Example: No Evidence Needed

```php
// STRUCTURE_ORPHAN (binary: is/isn't orphan)
if ($structure['is_orphan'] === true) {
    return self::violation(
        self::SEVERITY_HIGH,
        'Page is an orphan (no internal inbound links).'
        // No evidence: binary state
    );
}
```

---

## FRONTEND DEVELOPERS: EVIDENCE DISPLAY RULES

### Display Pattern

```javascript
// ALWAYS check if evidence exists
if (violation.measured_value !== undefined) {
    html += `<div class="text-xs text-gray-400">
        Current: ${violation.measured_value}
        ${violation.expected_value ? ` | Expected: ${violation.expected_value}` : ''}
    </div>`;
}
```

### Styling Guidelines

- **Color:** Muted gray (`text-gray-400` or similar)
- **Size:** Smaller than explanation (`text-xs` vs `text-sm`)
- **Position:** Below explanation, not above
- **Visibility:** Hidden by default, shown on expansion (progressive disclosure)

---

## EXPLICIT WARNINGS

### ⚠️ WARNING 1: Evidence MUST NOT Be Used for Decision-Making

**Prohibited:**

```javascript
// ❌ NEVER DO THIS
if (page.violations.some(v => v.measured_value < threshold)) {
    blockPagePublish();
}
```

**Reason:** Evidence is observational metadata. Verdicts are authoritative. Using evidence for decisions risks drift if thresholds change.

---

### ⚠️ WARNING 2: Evidence Strings Are Not Parseable Contracts

**Prohibited:**

```javascript
// ❌ NEVER DO THIS
const threshold = parseInt(violation.expected_value.match(/\d+/)[0]);
if (violation.measured_value < threshold) {
    // ...
}
```

**Reason:** `expected_value` is a human-readable string (e.g., "10-60 characters"). Its format may change. Parse at your own risk.

---

### ⚠️ WARNING 3: Confidence Levels Are Informational Only

**Prohibited:**

```javascript
// ❌ NEVER DO THIS
if (violation.confidence === 'low') {
    ignoreViolation();
}
```

**Reason:** Confidence describes measurement certainty, not verdict validity. A "low confidence" verdict is still authoritative.

---

## OBSERVATION MODE LIFECYCLE

### Current State: v1.5 (Passive Deepening)

**Phase J.0:** Policy Engine (Read-Only, Frozen)  
**Phase J.1:** UI Translation (Progressive Disclosure)  
**Phase J.2.1:** Observability Enrichment (Evidence Metadata)  
**Phase J.2.2:** Documentation & Verification (This Guide)

**System Capabilities:**

- ✅ Evaluate pages against 8 SEO policies
- ✅ Display verdicts with human-friendly language
- ✅ Show evidence for 4 priority rules (Title, H1, HTTP Status, Depth)
- ❌ No enforcement (no auto-fix, no blocking)
- ❌ No authority (no automated actions)

### Future Phases (Not Implemented)

**Phase J.3:** Authority Activation (Future)  

- Evidence might be used for automated remediation
- **NOT ACTIVE:** System remains in observation mode

**v2.0:** Active Authority (Future)  

- System might block or auto-fix issues
- **NOT ACTIVE:** Current freeze prevents this

---

## ROLLBACK GUARANTEE

If Phase J.2.1 evidence enrichment causes issues:

**Rollback Procedure:**

```bash
git checkout HEAD~1 app/Services/Policy/PolicyRuleSet.php
git checkout HEAD~1 app/Services/Policy/PolicyEvaluator.php
php artisan cache:clear
```

**Result:** Evidence fields return to `null`. UI degrades gracefully.  
**Time:** <5 minutes  
**Impact:** Zero (UI handles null evidence)

---

## SUMMARY

✅ **DO:**

- Trust policy verdicts (`status`, `severity`)
- Display evidence as secondary context
- Handle null evidence gracefully
- Use muted styling for evidence

❌ **DON'T:**

- Derive verdicts from evidence
- Parse `expected_value` strings programmatically
- Use evidence for automated decisions
- Display evidence more prominently than verdicts

**Remember:** Observation Mode is about *understanding* the system, not *controlling* it.

---

**Document Owner:** Verification & Documentation Authority  
**Last Updated:** 2026-02-05  
**Next Review:** Upon Phase J.3 activation
