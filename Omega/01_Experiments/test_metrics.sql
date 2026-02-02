-- D1: Crawl Stability
-- Success Rate
SELECT 
    'Crawl Stability' as metric,
    (SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as value
FROM crawl_logs 
GROUP BY crawl_run_id 
ORDER BY id DESC LIMIT 1;

-- Latency Score
SELECT 
    'Latency Avg (ms)' as metric,
    AVG(response_ms) as value
FROM crawl_logs 
GROUP BY crawl_run_id 
ORDER BY id DESC LIMIT 1;

-- D2: Audit Cleanliness
SELECT 
    'Audit Score' as metric,
    100 - (SUM(CASE WHEN severity = 'critical' THEN 5 ELSE 0 END) + SUM(CASE WHEN severity = 'high' THEN 2 ELSE 0 END)) as value
FROM seo_audits
WHERE status = 'open';

-- D3: Metadata Coverage
SELECT 
    'Meta Density' as metric,
    (COUNT(m.id) / COUNT(p.id)) * 100 as value
FROM pages p
LEFT JOIN seo_meta m ON p.id = m.page_id
WHERE m.title IS NOT NULL AND m.title != '';

-- D4: Structural Integrity (Orphan Rate)
SELECT 
    'Orphan Rate' as metric,
    (SUM(CASE WHEN il.id IS NULL THEN 1 ELSE 0 END) / COUNT(p.id)) * 100 as value
FROM pages p
LEFT JOIN internal_links il ON p.id = il.to_page_id
WHERE p.path != '/';

-- DRIFT: Ghost (In Sitemap/Database, but Dead in Reality)
SELECT 
    'Ghost Drift Count' as metric,
    COUNT(*) as value
FROM pages
WHERE http_status_last >= 400;

-- DRIFT: Zombie (Implicit Risk)
-- In v1, we cannot strictly prove a page wasn't in the sitemap without a 'source' column.
-- We use 'Orphans' as a proxy for 'Not well linked'.
SELECT 
    'Zombie Risk (Orphans)' as metric,
    (SUM(CASE WHEN il.id IS NULL THEN 1 ELSE 0 END)) as value
FROM pages p
LEFT JOIN internal_links il ON p.id = il.to_page_id
WHERE p.path != '/';
