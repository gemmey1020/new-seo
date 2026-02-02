# BP-004: Passive Deepening (Phase 1.5)

**Status:** DRAFT
**Role:** Architecture Spec
**Goal:** Close SEO gaps (Content/Structure) via "Passive Observation" only.
**Constraint:** NO WRITES. NO AUTOMATION.

## 1. ARCHITECTURE: CONTENT SERVICE
A new Service Layer dedicated to analyzing the *quality* and *structure* of the page body.
Unlike `HealthService` (which is holistic), `ContentService` looks inside the DOM of a generic `CrawlLog`.

### Component: `ContentService`
*   **Input:** `CrawlLog` (HTML Body).
*   **Logic:**
    1.  **Readability Engine:** Flesch-Kincaid / Automated Readability Index (ARI).
    2.  **Structure Scanner:** H tag hierarchy (H1 -> H2 -> H3).
    3.  **Keyword Analyzer:** Frequency density (TF-IDF Lite) of target keywords (if defined).
*   **Output:** `ContentScore` (DTO).

### Integration
*   Called via `HealthService`? **NO.** Kept separate to preserve `HealthService` speed.
*   New API: `GET /api/v1/sites/{site}/pages/{page}/content_analysis`.

## 2. DEEP AUDIT EXPANSION (Compliance Layer)
Enhancing `SeoAudit` logic to detect complex issues.

### New Rules (Passive)
1.  **Canonical Mismatch:**
    *   Logic: `Page.url` !== `DOM <link rel="canonical" href="...">`.
    *   Severity: HIGH.
2.  **Heading Hierarchy:**
    *   Logic: H2 found before H1. H4 immediately after H2 (skipping H3).
    *   Severity: MEDIUM.
3.  **JSON-LD Schema Validation:**
    *   Logic: `json_decode(script[type="application/ld+json"])`.
    *   Check: Syntax Error? Missing `@type`?
    *   Severity: HIGH.

## 3. CAPABILITY COVERAGE (Gap Closure)
| Capability | Old Status (v1.3) | New Status (v1.5) | Mechanism |
|:---|:---|:---|:---|
| Content Readability | 🔴 Not Covered | ✅ Covered | `ContentService` (Flesch) |
| Keyword Presence | 🔴 Not Covered | ✅ Covered | `ContentService` (Density) |
| H-Tag Hierarchy | 🟡 Partial | ✅ Covered | `structure_scanner` |
| Canonical Checks | 🟡 Partial | ✅ Covered | `CanonicalMismatchRule` |
| Schema Syntax | 🟡 Partial | ✅ Covered | `JsonLdValidator` |

**Projected Coverage:** 90% of Enterprise Target (Excluding Sitemaps/Writes).

## 4. RISK REGISTER (Passive Safety)
Even read-only logic has risks.

| Risk | Description | Mitigation |
|:---|:---|:---|
| **Performance (CPU)** | content analysis (regex/parsing) is expensive. | **On-Demand Only.** Do not run on every crawl. API endpoint triggers analysis or dedicated async job. |
| **False Positives (Schema)** | Valid schema marked invalid due to strict parsing. | Use lenient parser. Report "Syntax Error" only on total failure. |
| **Privacy** | Analyzing content might expose PII in logs. | Do not log raw body content. Log scores only. |

## 5. DESIGN & CONTRACTS
See `HC-004` for data schemas.
