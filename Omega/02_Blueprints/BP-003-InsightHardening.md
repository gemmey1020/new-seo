# BP-003: Insight Hardening (v1.3)

**Status:** DRAFT
**Context:** v1.3 Trust & Transparency
**Scope:** Read-Only Logic (No Mutations)

## 1. Objectives
To tell the user *how much they should trust* the Health Score and *why* it is what it is.

## 2. Hardening Logic

### A. Confidence Score (Data Sufficiency)
*   **Definition:** Is the data sample large enough to be statistically significant?
*   **Metric:** `Confidence %`
*   **Calculation:**
    *   `Crawl_Size_Factor`: Valid Pages / Total Pages (If < 10% crawled, Confidence drops).
    *   `History_Factor`: Number of runs (5 runs = 100%, 1 run = 20%).
    *   `Formula`: `(Crawl_Size_Factor * 50) + (History_Factor * 50)`.

### B. Noise Detection (Signal vs Noise)
*   **Definition:** Is an error a glitch or a pattern?
*   **Logic:**
    *   If `crawl_logs` show 500 error in Run N, check Run N-1 and N-2.
    *   If Error exists in >50% of history -> **Persistent** (Real Issue).
    *   If Error exists in <20% of history -> **Transient** (Noise).
*   **Output:** `Noise Flag` in Issue List.

### C. Explainability (The "Why")
*   **Definition:** Human-readable reasoning for the Score.
*   **Output:** Array of strings.
    *   "Score penalized -15pts due to High Latency (Avg 1200ms)."
    *   "Score penalized -20pts due to 4 Critical Audits."
    *   "Confidence Low: Only 5% of site crawled."

## 3. Schema Updates (HC-003 Extension)

### Health Object (Additions)
```json
{
  ...
  "confidence": {
    "score": 80, // 0-100
    "level": "HIGH", // HIGH, MEDIUM, LOW
    "factors": ["Small Sample Size"]
  },
  "explanation": [
    "Severe penalty from 404 Drifts.",
    "Latency is excellent."
  ]
}
```
