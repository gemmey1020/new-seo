# v1.2 HANDOFF: INSIGHT REFINEMENT

**Date:** 2026-02-02
**Version:** v1.2
**Status:** STABLE & FROZEN
**Reference:** HC-002 (Health Contract Extended)

## 1. SCOPE SUMMARY
v1.2 extends the Intelligence Layer with refined accuracy metrics without altering the core Read-Only guarantees.

### Features Delivered
1.  **State Drift:**
    *   Identifies pages claiming to be active but returning non-200 status codes.
    *   Severity based on ratio (<1% Safe, >5% Critical).

2.  **Latency Penalty:**
    *   Stability Score now accounts for server speed.
    *   Formula: `(Success Rate * 70%) + (Latency Score * 30%)`.
    *   Ensures a "slow but 200 OK" site is not rated 100/100.

3.  **Trend History:**
    *   Visualizes the last 5 Crawl Runs in the dashboard.
    *   Allows users to spot degradation trends.

## 2. COMPLIANCE & SAFETY
*   **HC-002:** Fully compliant. All new fields (`state`, `history`) are implemented strictly.
*   **HC-001:** Backwards compatible. Base fields preserved.
*   **Read-Only:** Zero mutations introduced.

## 3. FREEZE STATUS
v1.2 is now **FROZEN**.
Future development should target v2 (Active Authority).

---
**SIGNED OFF:**
Antigravity
