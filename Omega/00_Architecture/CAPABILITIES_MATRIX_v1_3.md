# CAPABILITIES MATRIX v1.3

**System:** Intelligence Layer
**Role:** Passive Observer

## 1. ENABLED INSIGHTS (Supported)

### A. Health & Performance
| Feature | Description | Metric/Logic |
|:---|:---|:---|
| **Stability Score** | Server reliability | `(Success% * 70) + (Latency * 30)` |
| **Latency Penalty** | Speed impact on score | Bucketed (<200ms to >2000ms) |
| **Trend History** | Stability over time | Last 5 Crawl Runs |

### B. Drift & Accuracy
| Feature | Description | Metric/Logic |
|:---|:---|:---|
| **Ghost Drift** | 404s in Sitemap | `count(status >= 400)` |
| **State Drift** | Non-200 Active Pages | `count(status != 200)` |
| **Zombie Risk** | Orphaned Pages | `count(inbound_links == 0)` |
| **Noise Detection**| Signal Validity | Persistent (3/3 runs) vs Transient |

### C. Trust & Context
| Feature | Description | Metric/Logic |
|:---|:---|:---|
| **Confidence** | Data Sufficiency | Yields HIGH/MED/LOW + Reasons |
| **Explainability**| Human Reasoning | "Why" the score is X (NL strings) |
| **Readiness** | Authority Gate | Binary Boolean (Ready/Not Ready) |

## 2. NON-FEATURES (Explicitly Disabled)

Processing any of the following requires **v2 (Active Authority)**:

*   ❌ **Sitemap Generation:** Cannot write XML files.
*   ❌ **Auto-Redirects:** Cannot modify `redirects` table.
*   ❌ **Content Pruning:** Cannot delete `pages`.
*   ❌ **External Sync:** No GSC / Indexing API calls.
*   ❌ **Alerting:** No Email/Slack dispatch (Passive API pull only).

## 3. SYSTEM BOUNDARIES
*   **Read-Only:** Strictly enforced.
*   **Local Only:** No 3rd party API dependencies.
*   **On-Demand:** Logic runs only when requested (or cached).
