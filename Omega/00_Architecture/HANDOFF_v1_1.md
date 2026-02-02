# v1.1 HANDOFF: INTELLIGENCE LAYER

**Date:** 2026-02-02
**Version:** v1.1 (Readiness / Intelligence)
**Status:** STABLE & FROZEN
**Reference:** HC-001 (Health Contract)

## 1. SCOPE SUMMARY
v1.1 introduces the "Active Authority" Brain without the Hands. It is a strictly observational layer that sits on top of the v1 Infrastructure.

### Features Delivered
1.  **HealthService (The Brain):**
    *   Deterministic scoring (0-100) based on Stability, Compliance, Metadata, and Structure.
    *   Strict caching (5m TTL).
    *   Source-of-Truth derivation from `crawl_logs`, `seo_audits`, and `pages`.

2.  **Drift Detection (The Watchdog):**
    *   **Ghost Drift:** Identifies pages in Database that are dead (404/500).
    *   **Zombie Risk:** Identifies pages with no inbound links (Orphans).
    *   **Verdict:** Returns `CRITICAL` warnings if data integrity is compromised.

3.  **Readiness Gate:**
    *   Boolean `ready` flag.
    *   Blockers list preventing premature automation.

4.  **Intelligence Dashboard:**
    *   Visual "Health Score" badge.
    *   Drift Monitor panel.
    *   Readiness Verdict panel.
    *   **Strictly Read-Only:** All operational triggers removed from this view.

## 2. GUARANTEES (The Contract)
*   **Zero Mutation:** v1.1 code DOES NOT write to the database (except Cache).
*   **Zero Side Effects:** v1.1 code DOES NOT trigger jobs, crawls, or external requests.
*   **Determinism:** The same database state will ALWAYS yield the same Health Score.
*   **Isolation:** The Intelligence Layer is decoupled from the Execution Layer.

## 3. KNOWN LIMITS
*   **Drift Proxy:** "Zombie Drift" is currently proxied via Orphan inspection because v1 does not have a `sitemap_source_content` table to compare against. This is a known acceptable limitation for v1.1.
*   **Audit Score:** Deductions are based on `severity`. False positives in Audit Rules (e.g., 500 errors on valid pages) will lower the Health Score.

## 4. EXPLICIT NON-FEATURES (v2 Scope)
*   **Sitemap Generation:** We DO NOT write XML files.
*   **Redirect Execution:** We DO NOT redirect traffic.
*   **Auto-Fixing:** We DO NOT patch audit issues.

---
**NEXT STEPS (v2):**
Only when `Readiness Verdict` is TRUE for >3 distinct sites should v2 (Active Authority) be considered.

**SIGNED OFF:**
Jemy (Founder / Architect)
