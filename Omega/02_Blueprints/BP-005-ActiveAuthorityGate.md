# BP-005: Active Authority Gate (v2)

**Status:** DRAFT SPECIFICATION
**Goal:** Define rules for granting "Right to Write" (Mutations).
**Philosophy:** "Power is granted only when Safety is guaranteed."

## 1. ACTION CLASSIFICATION

### Class A: SAFE (Autonomous Candidates)
*Actions that are mathematically verifiable or non-destructive.*
*   **Sitemap Generation:**
    *   *Logic:* `CrawlRun` success > 90% + No Ghost Drift = Valid Sitemap.
    *   *Mechanism:* Write `sitemap.xml` based on `CrawlRun` pages.
*   **Image Optimization:**
    *   *Logic:* Lossless compression.
    *   *Mechanism:* Replace file with smaller byte size, same visual.

### Class B: GATED (Human Approval Required)
*Actions that alter traffic flow or indexability.*
*   **Meta Updates:**
    *   *Logic:* Writing new Title/description.
    *   *Mechanism:* Update `seo_meta` table.
    *   *Gate:* **Strict Approval Queue**.
*   **Redirect Creation (Fixing Ghosts):**
    *   *Logic:* 404 (Persistent) -> 301 to Target.
    *   *Mechanism:* Insert into `redirects` table.
    *   *Gate:* **Batch Approval**.

### Class C: FORBIDDEN (Never Automate)
*Actions with unacceptable risk.*
*   ❌ **Content Deletion:** Pruning "Zombies". Human must click delete.
*   ❌ **Robots.txt Block:** Blocking entire dirs. Human only.
*   ❌ **AI Content Generation:** Writing body text without review.

## 2. CONFIDENCE THRESHOLDS (The "Key")
Authority is **LOCKED** by default. It unlocks per-site only if:

1.  **History Depth:** > 5 Successful Crawl Runs (History Factor 1.0).
2.  **Stability Score:** > 80 (High Stability).
3.  **Drift Status:**
    *   For Sitemap writing: Zero Critical "State Drift".
    *   For Redirects: Zero "False Positive" reports in last 7 days.
4.  **Confidence Level:** HIGH (Score > 80).

## 3. HUMAN APPROVAL FLOW (The "Hand")
The system proposes; the human disposes.

1.  **Proposal Phase:** System identifies action (e.g., "Fix 5 broken links").
    *   Status: `PENDING_APPROVAL`.
2.  **Review Phase:** Admin views **Diff Preview** (Before vs After).
3.  **Execution Phase:** Admin clicks "Approve Batch".
    *   Status: `QUEUED` -> `EXECUTING` -> `COMPLETED`.
4.  **Verification:** System immediately crawls modified URLs to confirm fix.

## 4. ROLLBACK STRATEGY (The "Undo")
Every mutation batch must be reversible.

*   **Database:** Transaction-wrapped writes.
*   **Snapshot:** Before applying Class B actions, store JSON snapshot of affected rows.
*   **Undo Button:** Available for 24 hours. Reverts specific rows to snapshot state.

## 5. KILL SWITCHES (The "Brake")
Mechanisms to immediately strip authority.

1.  **Latency Trigger:** If `HealthService` p95 > 500ms -> **Disable All Writes**.
2.  **Error Trigger:** If 500 Error rate > 1% -> **Disable All Writes**.
3.  **Manual Override:** Env var `AUTHORITY_MODE=off`.
4.  **Drift Panic:** If Sitemap Drift spikes > 50% post-update -> **Auto-Revert** and Lock System.

## 6. ARCHITECTURE CHANGES (Required for v2)
*   New Service: `AuthorityService` (Handles mutation logic).
*   New Table: `action_proposals` (Stores pending actions).
*   New Table: `action_logs` (Audit trail of who approved what).

---
**Next Step:** Implement `AuthorityService` (Stub) and Tables? **NO.**
**Current Directive:** Design Only.
