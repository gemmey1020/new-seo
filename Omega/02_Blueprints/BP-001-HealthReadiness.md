# BP-001: Site Health & Readiness Logic (Read-Only)

**Status:** DRAFT
**Context:** v1 Freeze -> v1.1 Readiness
**Scope:** Read-Only Intelligence using EXISTING v1 data.

## 1. Objective
To deterministically assess if a Site is stable ("Healthy") and if its Observed State matches its Declared State ("Ready" for Authority).

## 2. Health Dimensions
We evaluate the site across 4 dimensions using existing `apps/Models` data.

### D1: Crawl Stability (Technical)
*Does the server respond reliably?*
*   **Metric:** `Success Rate` = (200 OK Responses / Total Crawled Pages)
*   **Metric:** `Latency Score` = Average `response_ms` (Target < 500ms)
*   **Source:** `crawl_logs` (latest run)

### D2: Audit Cleanliness (Compliance)
*Is the site free of known critical errors?*
*   **Metric:** `Audit Score` = 100 - (Critical Errors * 5) - (High Errors * 2)
*   **Source:** `seo_audits` (status=open)

### D3: Metadata Coverage (Content)
*Does the site have basic SEO hygiene?*
*   **Metric:** `Meta Density` = % of pages with non-empty Title & Description.
*   **Source:** `seo_meta` JOIN `pages`

### D4: Structural Integrity (Graph)
*Is the site interconnected?*
*   **Metric:** `Orphan Rate` = % of pages with 0 inbound internal links.
*   **Source:** `pages` LEFT JOIN `internal_links`

---

## 3. The Health Score Algorithm
A single 0-100 score to benchmark sites.

```sql
Score = (
  (Crawl_Success_Rate * 0.40) +  -- 40% Weight (Foundation)
  (Audit_Score * 0.30) +         -- 30% Weight (Compliance)
  (Meta_Density * 0.20) +        -- 20% Weight (Optimization)
  ((100 - Orphan_Rate) * 0.10)   -- 10% Weight (Structure)
)
```

**Interpretation:**
*   **> 90**: Excellent. Ready for Authority.
*   **70-89**: Good. Minor clean up needed.
*   **< 70**: Unstable. NOT ready for automation.

---

## 4. Drift Detection (The "Readiness" Gate)
Drift is the delta between "Declared Intent" (Sitemap) and "Observed Reality" (Crawl).

### Types of Drift
1.  **Ghost Drift** (In Sitemap, Not Found)
    *   *Query:* `SELECT url FROM sitemap_source_urls WHERE url NOT IN (SELECT url FROM pages WHERE last_crawled_at > ...)`
    *   *Risk:* We are promising content that doesn't exist (404s).
2.  **Zombie Drift** (Found, Not In Sitemap)
    *   *Query:* `SELECT url FROM pages WHERE url NOT IN (SELECT url FROM sitemap_source_urls)`
    *   *Risk:* Wasted crawl budget, old content leaking.
3.  **State Drift** (In Sitemap vs Actual Status)
    *   *Query:* Sitemap says "Live", Crawl says "301/404/500".

### Readiness Flag
A site is **READY** for v1.1 Authority only if:
1.  Ghost Drift < 1%
2.  State Drift (Critical) = 0
3.  Overall Health Score > 80

---

## 5. Sample SQL Queries (Implementation Guide)

### H1: Success Rate
```sql
SELECT 
    (SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
FROM crawl_logs 
WHERE crawl_run_id = ?
```

### D4: Orphan Check
```sql
SELECT COUNT(*) 
FROM pages p 
LEFT JOIN internal_links il ON p.id = il.to_page_id 
WHERE p.site_id = ? 
AND il.id IS NULL 
AND p.path != '/'
```

---

## 6. Risk Flags (Blocking v1.1)

| Flag | Meaning | Action |
| :--- | :--- | :--- |
| **CRITICAL_5XX_SPIKE** | >10% of pages returning 500 errors. | **BLOCK.** Do not attempt to automate or rebuild links. |
| **FATAL_DRIFT** | >20% mismatch between Sitemap and Crawl. | **BLOCK.** Site architecture is chaotic. Needs manual fix. |
| **METADATA_VOID** | >50% missing titles/descriptions. | **WARN.** Automation will simply propagate empty data. |

---
*Authored by Antigravity in Omega Lab.*
