# BROWSER_VERIFICATION_CHECKLIST_J2_2

**Phase:** J.2.2 — Verification & Documentation
**Scope:** Observational UI Verification
**Status:** READY FOR EXECUTION

---

## 1. PRE-CONDITIONS

- [ ] System is running (`php artisan serve`, `npm run dev`)
- [ ] Database contains recent crawl data with violations
- [ ] At least one page triggers an enriched rule (Title Length, H1, HTTP, Depth)
- [ ] At least one page triggers a non-enriched rule (Meta Desc, Orphan)
- [ ] User is logged in (if auth required) or accessing public dashboard

---

## 2. VERIFICATION AREAS

### 2.1 Overview Dashboard (`/sites/{id}`)

**Objective:** Confirm site-level status is unaffected by evidence.

| Step | Action | Expected Result | Failure Signal |
|:-----|:-------|:----------------|:---------------|
| 1 | Load Dashboard | Status badge (PASS/WARN/FAIL) matches server stats | Badge flickers or changes client-side |
| 2 | Check Metrics | Violation counts match database exactly | Counts differ from DB |
| 3 | Inspect Console | No JS errors related to `measured_value` | "undefined" errors in console |

**Notes:** Dashboard aggregates counts; should not render individual evidence.

---

### 2.2 Audits Center (`/sites/{id}/pages`)

**Objective:** Verify evidence rendering in list view (if applicable) or summary.

| Step | Action | Expected Result | Failure Signal |
|:-----|:-------|:----------------|:---------------|
| 1 | Filter "Needs Attention" | List shows pages with FAIL status | Pages missing or wrong status |
| 2 | Observe Rows | Policy column shows "FAIL" or "WARN" | Status derived from evidence locally |
| 3 | Check Colors | Red used ONLY for "CRITICAL" / "FAIL" | Red used for non-critical issues |
| 4 | Look for Evidence | Evidence text NOT visible in compact rows (progressive disclosure) | Evidence clutters list view |

---

### 2.3 Page Details (`/sites/{id}/pages/{page_id}`)

**Objective:** Verify progressive disclosure and evidence rendering.

| Step | Action | Expected Result | Failure Signal |
|:-----|:-------|:----------------|:---------------|
| 1 | Load Page Details | Status badge visible, Violations list collapsed/compact | Evidence visible immediately |
| 2 | Find Enriched Rule | Locate `CONTENT_TITLE_LENGTH` violation | Rule missing |
| 3 | Expand Violation | "Show Details" reveals explanation AND evidence | Evidence missing after expand |
| 4 | Verify Fields | `Current: X` and `Expected: Y` visible | Fields undefined or raw JSON |
| 5 | Verify Styling | Evidence text is muted/gray (secondary) | Evidence same size/color as verdict |
| 6 | Find Non-Enriched | Locate `CONTENT_META_DESC` violation | Rule missing |
| 7 | Expand Violation | Explanation visible, NO "Current/Expected" block | "null" or empty div visible |

---

### 2.4 Structure / Links (`/sites/{id}/structure`)

**Objective:** Verify `STRUCTURE_DEPTH` evidence.

| Step | Action | Expected Result | Failure Signal |
|:-----|:-------|:----------------|:---------------|
| 1 | Find Deep Page | Locate page with Depth > 3 | Page missing |
| 2 | Check Violation | Violation present for Depth | Violation missing |
| 3 | Verify Evidence | `Current: 4 (or more)` | Value mismatch |

---

### 2.5 Crawl Monitor (`/sites/{id}/crawls`)

**Objective:** Confirm observability only (read-only).

| Step | Action | Expected Result | Failure Signal |
|:-----|:-------|:----------------|:---------------|
| 1 | View Recent Crawl | Status `completed`, Stats static | Status changes unexpectedly |
| 2 | Check Logs | No "Policy Correction" log entries | Logs show auto-fix attempts |

---

## 3. NON-NEGOTIABLES

- [ ] **Authority:** UI MUST NOT calculate "Pass/Fail" from `measured_value`.
- [ ] **Color Semantics:** ORANGE = FAIL (standard), RED = CRITICAL (urgent).
- [ ] **Null Safety:** No `undefined` or `null` printed on screen.

## 4. SIGN-OFF

**Verified By:** ____________________
**Date:** ____________________
**Status:** PASS / FAIL
