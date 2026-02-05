# PHASE J.2 — OBSERVABILITY ENRICHMENT DESIGN

**Role:** Senior Systems Architect & Data Observability Designer  
**Date:** 2026-02-05  
**Status:** DESIGN PROPOSAL  
**Constraint:** Read-Only, Zero Authority, Zero Enforcement

---

## EXECUTIVE SUMMARY

**Current State:** Policy violations expose *judgment* (FAIL) without *evidence* (why).

**Problem:** UI cannot answer "What value caused this?" because violations lack measured context.

**Solution:** Enrich violation payload with measured values, expected thresholds, comparison operators, and confidence metadata—**without changing verdict logic**.

**Impact:** Phase J.1 UI can now display "Current: 5 chars | Expected: 10-60 chars" instead of just "Title is too short".

**Guarantee:** Policy Layer remains the sole judge. This phase only improves **signal clarity**, not **behavior**.

---

## PROBLEM DIAGNOSIS

### Current Violation Structure

**From `PolicyEvaluator.php` (Lines 36-43):**

```php
$violations[] = [
    'policy_code' => $code,
    'severity' => $result['severity'],
    'field' => $rule['field'],
    'expected' => 'PASS',  // Generic, not specific
    'actual' => 'FAIL',     // No measured value
    'explanation' => $result['explanation'],
];
```

**Analysis of Current Output:**

| Violation Code | Current Explanation | Missing Evidence |
|:---------------|:-------------------|:-----------------|
| `CONTENT_TITLE_LENGTH` | "Title is too short (< 10 chars)." | **Actual length** (e.g., 5) |
| `CONTENT_H1_COUNT` | "Multiple H1 headings found (should be 1)." | **Actual count** (e.g., 3) |
| `STRUCTURE_DEPTH` | "Page depth is greater than 3 clicks from home." | **Actual depth** (e.g., 5) |
| `INDEX_HTTP_STATUS` | "HTTP Status is 404 (expected 200)." | ✅ **Has value** (404) |

**Evidence Gap Summary:**

- 6 of 8 rules expose judgment without measurement
- Only `INDEX_HTTP_STATUS` includes actual value in explanation
- UI cannot programmatically extract measured values (they're embedded in strings)

### 4-Question Explainability Test

For each violation, can we answer:

| Question | Current Capability | Example |
|:---------|:------------------|:--------|
| **What is wrong?** | ✅ YES | "Title is too short" |
| **Where is it?** | ✅ YES | `field: 'meta.title'` |
| **How bad is it?** | ✅ YES | `severity: 'WARNING'` |
| **What value caused this?** | ❌ **NO** | Missing `measured_value: 5` |

**Verdict:** 3 of 4 questions answered. **Evidence gap confirmed.**

---

## STRATEGY OPTIONS

### Option A: Inline Enrichment (Within PolicyRule)

**Approach:** Modify `PolicyRuleSet::violation()` to accept optional evidence parameters.

**Architecture:**

```php
// In PolicyRuleSet.php
'CONTENT_TITLE_LENGTH' => [
    'evaluate' => function ($page, $analysis) {
        $len = strlen($page->meta->title ?? '');
        if ($len < 10) {
            return self::violation(
                self::SEVERITY_WARNING, 
                'Title is too short (< 10 chars).',
                measured: $len,       // NEW
                expected: '10-60',    // NEW
                comparison: 'below'   // NEW
            );
        }
        return self::pass();
    }
]
```

**Pros:**

- Evidence close to logic
- Single atomic change
- High confidence (logic knows what it measured)

**Cons:**

- Requires editing each rule (8 rules)
- Increases rule verbosity
- Mixes verdict with metadata

**Drift Risk:** ⚠️ MEDIUM (rules become more complex)

---

### Option B: Post-Evaluation Enrichment Layer

**Approach:** Add a separate enrichment pass *after* violations are collected.

**Architecture:**

```php
// New class: ViolationEnricher.php
class ViolationEnricher {
    public function enrich(array $violations, Page $page): array {
        return array_map(function($violation) use ($page) {
            return $this->enrichSingle($violation, $page);
        }, $violations);
    }
    
    private function enrichSingle(array $violation, Page $page): array {
        // Lookup table: code => enrichment function
        $enrichers = [
            'CONTENT_TITLE_LENGTH' => fn() => [
                'measured_value' => strlen($page->meta->title ?? ''),
                'expected_value' => '10-60 characters',
                'comparison' => $this->compareLength(...),
            ],
            // ... other rules
        ];
        
        $enricher = $enrichers[$violation['policy_code']] ?? null;
        if ($enricher) {
            return array_merge($violation, $enricher());
        }
        return $violation;
    }
}
```

**Pros:**

- Separation of concerns (verdict vs evidence)
- Rules stay clean and focused
- Easy to add/remove enrichment without touching rules
- Rollback-friendly

**Cons:**

- Evidence separated from logic (potential drift)
- Re-computation (rules already measured, now measuring again)
- Duplicated knowledge

**Drift Risk:** ⚠️ MEDIUM (enricher can drift from rule logic)

---

### Option C: Hybrid Model (Recommended)

**Approach:** Modify `violation()` helper to accept evidence, but make it **optional**. Apply post-enrichment for rules that don't provide it.

**Architecture:**

```php
// PolicyRuleSet.php - Updated violation helper
private static function violation(
    string $severity, 
    string $explanation,
    array $evidence = []  // NEW: Optional evidence
): array {
    return [
        'status' => self::STATUS_FAIL,
        'severity' => $severity,
        'explanation' => $explanation,
        'evidence' => $evidence,  // NEW
    ];
}

// PolicyEvaluator.php - Enrichment pass
$violations[] = [
    'policy_code' => $code,
    'severity' => $result['severity'],
    'field' => $rule['field'],
    'expected' => 'PASS',
    'actual' => 'FAIL',
    'explanation' => $result['explanation'],
    // NEW: Merge evidence if provided by rule
    'measured_value' => $result['evidence']['measured'] ?? null,
    'expected_value' => $result['evidence']['expected'] ?? null,
    'comparison' => $result['evidence']['comparison'] ?? null,
    'confidence' => $result['evidence']['confidence'] ?? 'medium',
];
```

**Pros:**

- Rules can opt-in to evidence (inline clarity)
- Fallback to null if not provided (no breaking change)
- Clear migration path (gradual enrichment)
- Low drift risk (evidence lives with verdict)

**Cons:**

- Rules that don't provide evidence remain opaque

**Drift Risk:** ✅ LOW (evidence co-located with logic)

---

## RECOMMENDED ARCHITECTURE

**Selected:** **Option C: Hybrid Model**

### Justification

**Alignment with Phase J.1:**

- UI already expects `measured_value` and `expected_value` (from `ui.translation.helpers.js`)
- Progressive disclosure philosophy: show what we know, hide what we don't
- If `measured_value` is null, UI gracefully omits it (no breaking change)

**Alignment with Freeze Rules:**

- PolicyRuleSet remains the source of truth
- No new tables, no DB writes
- Deterministic: same input → same output

**Maintainability:**

- Evidence is **bound to verdict logic** (single atomic unit)
- Adding evidence to a rule is a single-line change
- Rolling back is trivial (remove `evidence` param)

**Risk Mitigation:**

- Backward compatible: `evidence = []` by default
- Non-breaking schema extension (additive only)
- Confidence field allows marking uncertainty

---

## CANONICAL VIOLATION SCHEMA v1.1

### BEFORE (Current v1.0)

```json
{
  "policy_summary": {
    "status": "FAIL",
    "violations_count": 2,
    "rules_evaluated": 8,
    "evaluated_at": "2026-02-05T00:20:00Z"
  },
  "violations": [
    {
      "policy_code": "CONTENT_TITLE_LENGTH",
      "severity": "WARNING",
      "field": "meta.title",
      "expected": "PASS",
      "actual": "FAIL",
      "explanation": "Title is too short (< 10 chars)."
    }
  ]
}
```

**Problems:**

- `expected: "PASS"` is generic, not specific
- `actual: "FAIL"` has no measured value
- UI cannot extract "5 characters" programmatically

---

### AFTER (Proposed v1.1-observability)

```json
{
  "policy_summary": {
    "status": "FAIL",
    "violations_count": 2,
    "rules_evaluated": 8,
    "evaluated_at": "2026-02-05T00:20:00Z"
  },
  "violations": [
    {
      "policy_code": "CONTENT_TITLE_LENGTH",
      "severity": "WARNING",
      "field": "meta.title",
      "expected": "PASS",
      "actual": "FAIL",
      "explanation": "Title is too short (< 10 chars).",
      
      // NEW: Evidence Enrichment
      "measured_value": 5,
      "expected_value": "10-60 characters",
      "comparison": "below_minimum",
      "confidence": "high",
      
      // NEW: Metadata (Read-Only, Non-Authoritative)
      "severity_weight": 0.6,
      "priority_rank": 3
    },
    {
      "policy_code": "INDEX_HTTP_STATUS",
      "severity": "CRITICAL",
      "field": "http_status_last",
      "expected": "PASS",
      "actual": "FAIL",
      "explanation": "HTTP Status is 404 (expected 200).",
      
      "measured_value": 404,
      "expected_value": 200,
      "comparison": "not_equal",
      "confidence": "high",
      
      "severity_weight": 1.0,
      "priority_rank": 1
    },
    {
      "policy_code": "CONTENT_H1_COUNT",
      "severity": "HIGH",
      "field": "h1_count",
      "expected": "PASS",
      "actual": "FAIL",
      "explanation": "Multiple H1 headings found (should be 1).",
      
      "measured_value": 3,
      "expected_value": "1",
      "comparison": "above_maximum",
      "confidence": "high",
      
      "severity_weight": 0.8,
      "priority_rank": 2
    }
  ]
}
```

### Field Definitions

| Field | Type | Required | Purpose | Authority Level |
|:------|:-----|:---------|:--------|:---------------|
| `policy_code` | string | YES | Violation identifier | Policy Layer |
| `severity` | string | YES | CRITICAL/HIGH/WARNING/etc. | Policy Layer |
| `field` | string | YES | Affected field | Policy Layer |
| `explanation` | string | YES | Human message | Policy Layer |
| `measured_value` | mixed | NO | Actual value that triggered violation | **Observability** |
| `expected_value` | mixed | NO | Threshold or range | **Observability** |
| `comparison` | string | NO | `below_minimum`, `above_maximum`, `not_equal`, `missing` | **Observability** |
| `confidence` | string | NO | `high`, `medium`, `low` | **Observability** |
| `severity_weight` | float | NO | 0.0-1.0 for UI sorting | **Observability** |
| `priority_rank` | int | NO | 1-N for grouping | **Observability** |

**Key Constraint:** New fields are **observability metadata only**. They do NOT influence Policy verdict.

---

## IMPLEMENTATION SPECIFICATION

### Phase J.2.1: Update Violation Helper

**File:** `app/Services/Policy/PolicyRuleSet.php`

**Change:**

```php
private static function violation(
    string $severity, 
    string $explanation,
    ?string $measured = null,      // NEW
    ?string $expected = null,      // NEW
    ?string $comparison = null,    // NEW
    string $confidence = 'medium'  // NEW
): array {
    $result = [
        'status' => self::STATUS_FAIL,
        'severity' => $severity,
        'explanation' => $explanation,
    ];
    
    // Evidence metadata (optional)
    if ($measured !== null) {
        $result['evidence'] = [
            'measured' => $measured,
            'expected' => $expected,
            'comparison' => $comparison,
            'confidence' => $confidence,
        ];
    }
    
    return $result;
}
```

**Risk:** ✅ ZERO (backward compatible, optional params)

---

### Phase J.2.2: Enrich PolicyEvaluator Output

**File:** `app/Services/Policy/PolicyEvaluator.php`

**Change (Lines 36-43):**

```php
if ($result['status'] === PolicyRuleSet::STATUS_FAIL) {
    $evidence = $result['evidence'] ?? [];
    
    $violations[] = [
        'policy_code' => $code,
        'severity' => $result['severity'],
        'field' => $rule['field'],
        'expected' => 'PASS',
        'actual' => 'FAIL',
        'explanation' => $result['explanation'],
        
        // NEW: Evidence fields (null if not provided)
        'measured_value' => $evidence['measured'] ?? null,
        'expected_value' => $evidence['expected'] ?? null,
        'comparison' => $evidence['comparison'] ?? null,
        'confidence' => $evidence['confidence'] ?? 'medium',
        
        // NEW: Severity metadata (read-only)
        'severity_weight' => $this->getSeverityWeight($result['severity']),
        'priority_rank' => $this->getPriorityRank($result['severity']),
    ];
    $failCount++;
}
```

**New Helper Methods (in PolicyEvaluator):**

```php
private function getSeverityWeight(string $severity): float {
    return match($severity) {
        'CRITICAL' => 1.0,
        'HIGH' => 0.8,
        'WARNING' => 0.6,
        'OPTIMIZATION' => 0.4,
        'ADVISORY' => 0.2,
        default => 0.5,
    };
}

private function getPriorityRank(string $severity): int {
    return match($severity) {
        'CRITICAL' => 1,
        'HIGH' => 2,
        'WARNING' => 3,
        'OPTIMIZATION' => 4,
        'ADVISORY' => 5,
        default => 99,
    };
}
```

**Risk:** ✅ LOW (additive fields, no removal)

---

### Phase J.2.3: Gradual Rule Enrichment

**Priority 1: Rules with Easy Evidence**

1. **CONTENT_TITLE_LENGTH** (Line 30-32):

```php
$len = strlen($page->meta->title ?? '');
if ($len === 0) {
    return self::violation(
        self::SEVERITY_HIGH, 
        'Title is missing.',
        measured: (string)$len,
        expected: '10-60 characters',
        comparison: 'missing',
        confidence: 'high'
    );
}
if ($len < 10) {
    return self::violation(
        self::SEVERITY_WARNING, 
        'Title is too short (< 10 chars).',
        measured: (string)$len,
        expected: '10-60 characters',
        comparison: 'below_minimum',
        confidence: 'high'
    );
}
```

1. **CONTENT_H1_COUNT** (Line 50-52):

```php
$count = $page->h1_count;
if ($count === 0) {
    return self::violation(
        self::SEVERITY_HIGH, 
        'Page has no H1 heading.',
        measured: (string)$count,
        expected: '1',
        comparison: 'missing',
        confidence: 'high'
    );
}
if ($count > 1) {
    return self::violation(
        self::SEVERITY_OPTIMIZATION, 
        'Multiple H1 headings found (should be 1).',
        measured: (string)$count,
        expected: '1',
        comparison: 'above_maximum',
        confidence: 'high'
    );
}
```

1. **INDEX_HTTP_STATUS** (Line 84-85):

```php
if ($page->http_status_last !== 200) {
    return self::violation(
        self::SEVERITY_CRITICAL, 
        "HTTP Status is {$page->http_status_last} (expected 200).",
        measured: (string)$page->http_status_last,
        expected: '200',
        comparison: 'not_equal',
        confidence: 'high'
    );
}
```

1. **STRUCTURE_DEPTH** (Line 72-73):

```php
if ($page->depth_level > 3) {
    return self::violation(
        self::SEVERITY_WARNING, 
        'Page depth is greater than 3 clicks from home.',
        measured: (string)$page->depth_level,
        expected: '≤3 clicks',
        comparison: 'above_maximum',
        confidence: 'high'
    );
}
```

**Priority 2: Rules with Unavailable Evidence**

- `STRUCTURE_ORPHAN`: No numeric value (boolean)
- `INDEX_CANONICAL`: URL comparison (string)
- `INDEX_ROBOTS`: Directive detection (boolean)
- `CONTENT_META_DESC`: Missing only (no length check currently)

**Strategy for Priority 2:**

- Leave `evidence` empty (null)
- UI progressive disclosure handles null gracefully
- Add `measurement_note` field if needed:

  ```php
  'measurement_note' => 'This is a boolean check, no numeric value available'
  ```

---

## NORMALIZATION PASS

### Issue 1: Severity Casing Inconsistency

**Problem:** PolicyRuleSet uses `'CRITICAL'`, but some views expect lowercase.

**Solution:**

```php
// In PolicyEvaluator.php output normalization
'severity' => strtoupper($result['severity']), // Enforce uppercase
```

**Alternative:** Add a constant map in UI translation dictionary (already done in Phase J.1).

**Recommendation:** **No backend change**. UI layer already normalizes via `severity.toLowerCase()`.

---

### Issue 2: Field Naming Consistency

**Current:**

- `meta.title` (dot notation)
- `h1_count` (snake_case)
- `structure.is_orphan` (dot notation)

**Analysis:** Mixed notation is acceptable if documented.

**Recommendation:** **No change**. Add documentation to clarify:

- Dot notation = nested accessor
- Snake case = direct field

---

### Issue 3: Null/Empty Violations Array

**Current:** If no violations, `violations: []`

**Recommendation:** ✅ KEEP. Empty array is semantically correct.

---

### Issue 4: Missing Expected/Actual Specificity

**Current:**

```json
"expected": "PASS",
"actual": "FAIL"
```

**Problem:** Too generic.

**Solution:** Keep for backward compatibility, but rely on new `expected_value` and `measured_value` fields in UI.

**Recommendation:**

- Keep `expected: "PASS"` (legacy)
- Use `expected_value: "10-60 characters"` (new standard)

---

## RISK ANALYSIS

### Risk 1: Evidence Drift from Logic

**Scenario:** Rule logic changes, but evidence enrichment is not updated.

**Example:**

```php
// Rule changes threshold from 10 to 15
if ($len < 15) { ... } 

// But evidence still says:
expected: '10-60 characters'  // DRIFT!
```

**Mitigation:**

- Evidence is co-located with logic (same function)
- Unit tests validate evidence matches thresholds
- Code review checklist

**Detection:**

```php
// Test case
$violation = evaluateRule('CONTENT_TITLE_LENGTH', $pageWith5CharTitle);
$this->assertEquals(5, $violation['measured_value']);
$this->assertStringContainsString('10', $violation['expected_value']);
```

**Likelihood:** ⚠️ MEDIUM  
**Impact:** 🔶 MEDIUM (UI shows wrong threshold, but verdict is still correct)

---

### Risk 2: UI Trusts Evidence Over Verdict

**Scenario:** UI developer uses `measured_value` to make verdict, bypassing Policy Layer.

**Example:**

```javascript
// BAD: UI logic making decisions
if (violation.measured_value < 10) {
    showRedBadge(); // UI is now enforcing!
}
```

**Mitigation:**

- Documentation: Evidence is **observability only**
- Code review: Flag any UI logic using measured_value for decisions
- Audit: Grep for conditional logic on `measured_value` in UI

**Detection:**

```bash
grep -r "measured_value.*if\|measured_value.*?" resources/views/
```

**Likelihood:** ⚠️ MEDIUM  
**Impact:** 🛑 CRITICAL (violates Phase J architecture)

---

### Risk 3: Performance Degradation

**Scenario:** Enrichment adds computation overhead.

**Analysis:**

- Evidence comes from already-measured values (no re-computation)
- Severity weight/rank are simple lookups (constant time)
- Impact: < 1ms per violation

**Mitigation:** Profile PolicyEvaluator before/after.

**Likelihood:** ✅ LOW  
**Impact:** 🟢 LOW

---

### Risk 4: Backward Compatibility Break

**Scenario:** Existing API consumers expect old schema.

**Analysis:**

- New fields are **additive** (not replacing)
- Old fields (`expected: "PASS"`) remain
- Null values are JSON-safe

**Mitigation:** Version API if needed (`/api/v1.1/pages`).

**Likelihood:** ✅ NONE (additive change)  
**Impact:** 🟢 NONE

---

## ROLLBACK PROCEDURE

### Immediate Rollback (< 1 hour)

**Step 1:** Revert `PolicyRuleSet.php`

```bash
git checkout origin/main app/Services/Policy/PolicyRuleSet.php
```

**Step 2:** Revert `PolicyEvaluator.php`

```bash
git checkout origin/main app/Services/Policy/PolicyEvaluator.php
```

**Step 3:** Clear cache

```bash
php artisan cache:clear
php artisan config:clear
```

**Impact:** UI will not show measured values, but will gracefully fall back (Phase J.1 UI handles null).

---

### Partial Rollback (Remove Evidence from Specific Rules)

If only one rule causes issues:

```php
// Change this:
return self::violation(
    self::SEVERITY_WARNING, 
    'Title is too short.',
    measured: $len,
    expected: '10-60',
    comparison: 'below',
    confidence: 'high'
);

// To this:
return self::violation(
    self::SEVERITY_WARNING, 
    'Title is too short.'
);
```

---

## REGRESSION DETECTION

### Automated Tests

**Test 1: Evidence Presence**

```php
public function test_violation_includes_evidence() {
    $page = Page::factory()->create(['meta' => ['title' => 'Hi']]);
    $result = (new PolicyEvaluator())->evaluate($page);
    
    $titleViolation = collect($result['violations'])
        ->firstWhere('policy_code', 'CONTENT_TITLE_LENGTH');
    
    $this->assertNotNull($titleViolation['measured_value']);
    $this->assertEquals(2, $titleViolation['measured_value']);
    $this->assertStringContainsString('10', $titleViolation['expected_value']);
}
```

**Test 2: Evidence Matches Logic**

```php
public function test_evidence_threshold_matches_logic() {
    // If logic says "< 10", evidence should say "10-60" or similar
    $page = Page::factory()->create(['meta' => ['title' => str_repeat('A', 9)]]);
    $result = (new PolicyEvaluator())->evaluate($page);
    
    $violation = collect($result['violations'])
        ->firstWhere('policy_code', 'CONTENT_TITLE_LENGTH');
    
    $this->assertEquals(9, $violation['measured_value']);
    $this->assertStringContainsString('10', $violation['expected_value']);
}
```

**Test 3: Verdict Unchanged**

```php
public function test_enrichment_does_not_change_verdict() {
    $page = Page::factory()->create([...]);
    
    // Disable enrichment
    config(['policy.enable_evidence' => false]);
    $resultBefore = (new PolicyEvaluator())->evaluate($page);
    
    // Enable enrichment
    config(['policy.enable_evidence' => true]);
    $resultAfter = (new PolicyEvaluator())->evaluate($page);
    
    // Status must be identical
    $this->assertEquals(
        $resultBefore['policy_summary']['status'],
        $resultAfter['policy_summary']['status']
    );
}
```

---

## READINESS VERDICT

### Pre-Implementation Checklist

- [x] Design reviewed against Phase J.1 constraints
- [x] Architecture preserves read-only guarantee
- [x] No authority introduced
- [x] No enforcement added
- [x] Backward compatible schema
- [x] Rollback plan defined
- [x] Test strategy documented

### Final Authority Audit

**Question:** Does this change introduce authority?  
**Answer:** ❌ NO. Evidence is observability metadata only.

**Question:** Does it alter verdict logic?  
**Answer:** ❌ NO. Verdict remains unchanged (test case required).

**Question:** Does it create a new source of truth?  
**Answer:** ❌ NO. Policy Layer remains sole source of truth. Evidence is derived from Policy.

---

## IMPLEMENTATION ROADMAP

### Phase J.2.1: Foundation (Week 1)

- [ ] Update `PolicyRuleSet::violation()` helper
- [ ] Add severity metadata helpers to `PolicyEvaluator`
- [ ] Write unit tests for evidence schema

### Phase J.2.2: Rule Enrichment (Week 2)

- [ ] Enrich `CONTENT_TITLE_LENGTH`
- [ ] Enrich `CONTENT_H1_COUNT`
- [ ] Enrich `INDEX_HTTP_STATUS`
- [ ] Enrich `STRUCTURE_DEPTH`

### Phase J.2.3: Documentation (Week 2)

- [ ] Update API documentation with schema v1.1
- [ ] Add evidence examples to FREEZE_POLICY.md
- [ ] Create evidence_guidelines.md for future rules

### Phase J.2.4: Verification (Week 3)

- [ ] Browser test with real violations
- [ ] Verify UI displays measured values
- [ ] Performance profiling
- [ ] Rollback drill (test rollback procedure)

---

## CLOSING STATEMENT

**Phase J.2 strengthens observability without shifting authority.  
Judgment remains frozen. Meaning becomes clearer.**

---

## APPENDIX A: Comparison Operator Reference

| Operator | Meaning | Example |
|:---------|:--------|:--------|
| `below_minimum` | Measured < Expected Min | Title: 5 chars (expected: 10-60) |
| `above_maximum` | Measured > Expected Max | H1 count: 3 (expected: 1) |
| `not_equal` | Measured ≠ Expected | HTTP Status: 404 (expected: 200) |
| `missing` | Measured = null/empty | Title missing (expected: present) |
| `out_of_range` | Measured outside range | Depth: 5 (expected: ≤3) |

---

## APPENDIX B: Confidence Level Guidelines

| Confidence | When to Use | Example |
|:-----------|:------------|:--------|
| `high` | Value is directly measurable, objective | String length, numeric count |
| `medium` | Value is inferred or derived | Canonical status (requires URL comparison) |
| `low` | Value is approximate or heuristic | Content quality score |

If confidence is `low`, consider adding `measurement_note` to explain uncertainty.
