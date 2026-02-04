<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Seo\Page;
use App\Models\Crawl\CrawlLog;

/**
 * Class ContentService
 * 
 * Implements Phase 1.5 Passive Deepening.
 * strictly Read-Only. On-Demand Analysis.
 */
class ContentService
{
    /**
     * Analyze a page's content for quality and structure.
     * 
     * @param Page $page
     * @return array Matches HC-004 Schema
     */
    public function analyze(Page $page): array
    {
        $body = $this->getBody($page);
        
        if (!$body) {
            return [
                'error' => 'Content unavailable (No recent crawl found or fetch failed)',
                'generated_at' => now()->toIso8601String()
            ];
        }

        // Parse HTML
        // Using simple DOMDocument or Regex for bounded parsing
        // We use DOMDocument for structure, strip_tags for readability
        $dom = new \DOMDocument();
        @$dom->loadHTML($body); // Suppress invalid HTML warnings

        // Parse Meta & Schemas (for Audit)
        $meta = $this->calcMeta($dom);
        $schemas = $this->calcSchemas($dom);

        return [
            'readability' => $this->calcReadability($body),
            'structure' => $this->calcStructure($dom),
            'keywords' => $this->calcKeywords($body),
            'meta' => $meta,
            'schemas' => $schemas,
            'generated_at' => now()->toIso8601String()
        ];
    }

    /**
     * Get Body from Cache or Live Fetch.
     */
    public function getBody(Page $page): ?string
    {
        // 1. Try Cache via last CrawlLog
        $lastLog = CrawlLog::where('page_id', $page->id)->latest('crawled_at')->first();
        if ($lastLog) {
            $key = "crawl_body_{$lastLog->crawl_run_id}_{$page->id}";
            $cached = Cache::get($key);
            if ($cached) return $cached;
        }

        // 2. Fallback: Live Fetch (On-Demand)
        try {
            $response = Http::timeout(5)->get($page->url);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            // Log error or ignore
        }

        return null;
    }

    private function calcReadability(string $html): array
    {
        $text = strip_tags($html);
        $wordCount = str_word_count($text);
        $sentences = preg_split('/[.!?]/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences) ?: 1;
        
        $avgSentenceLength = $wordCount / $sentenceCount;
        
        // Flesch-Kincaid Grade Level (Simplified Approximation)
        // 0.39 * (words/sentences) + 11.8 * (syllables/words) - 15.59
        // Syllable counting is complex, we use a proxy here (avg char len / 3)
        // This is a "Lite" version for MVP
        
        return [
            'score' => 0, // Placeholder for Flesch Score
            'grade_level' => 'N/A', // Placeholder
            'metrics' => [
                'word_count' => $wordCount,
                'sentence_count' => $sentenceCount,
                'avg_sentence_length' => round($avgSentenceLength, 1)
            ]
        ];
    }

    private function calcStructure(\DOMDocument $dom): array
    {
        $h1 = $dom->getElementsByTagName('h1')->length;
        $headings = [];
        
        // Collect H structure in order (naive scan)
        foreach ($dom->getElementsByTagName('*') as $node) {
            if (preg_match('/^h[1-6]$/i', $node->tagName)) {
                $headings[] = strtolower($node->tagName);
            }
        }

        $issues = [];
        if ($h1 === 0) $issues[] = "Missing H1 tag";
        elseif ($h1 > 1) $issues[] = "Multiple H1 tags found";

        // Check continuity
        // Example: H1 -> H3 (skip H2)
        $prevLevel = 0;
        foreach ($headings as $h) {
            $currLevel = (int) substr($h, 1);
            if ($prevLevel > 0 && $currLevel > $prevLevel + 1) {
                $issues[] = "Skipped heading level: H{$prevLevel} to H{$currLevel}";
            }
            $prevLevel = $currLevel;
        }

        return [
            'h1_count' => $h1,
            'h_structure' => array_slice($headings, 0, 20), // Truncate for display
            'issues' => array_unique($issues)
        ];
    }

    private function calcKeywords(string $html): array
    {
        // Placeholder: Needs definition of "Target Keywords".
        // For Phase 1.5, we just accept logic "Found/Not Found" if we knew what to look for.
        // Currently we return empty as Page model doesn't store "focus_keyword" yet.
        return [
            'detected' => []
        ];
    }

    private function calcMeta(\DOMDocument $dom): array
    {
        $canonical = null;
        $prev = null;
        $next = null;
        $robots = null;

        // Link Tags
        foreach ($dom->getElementsByTagName('link') as $link) {
            $rel = $link->getAttribute('rel');
            if ($rel === 'canonical') {
                $canonical = $link->getAttribute('href');
            } elseif ($rel === 'prev') {
                $prev = $link->getAttribute('href');
            } elseif ($rel === 'next') {
                $next = $link->getAttribute('href');
            }
        }

        // Meta Tags
        foreach ($dom->getElementsByTagName('meta') as $meta) {
            if (strtolower($meta->getAttribute('name')) === 'robots') {
                $robots = $meta->getAttribute('content');
            }
        }

        return [
            'canonical' => $canonical,
            'prev' => $prev,
            'next' => $next,
            'robots' => $robots
        ];
    }

    private function calcSchemas(\DOMDocument $dom): array
    {
        $schemas = [];
        foreach ($dom->getElementsByTagName('script') as $script) {
            if ($script->getAttribute('type') === 'application/ld+json') {
                $json = $script->textContent;
                $data = json_decode($json, true);
                
                $valid = (json_last_error() === JSON_ERROR_NONE);
                $schemas[] = [
                    'valid' => $valid,
                    'error' => $valid ? null : json_last_error_msg(),
                    'type' => $data['@type'] ?? 'Unknown'
                ];
            }
        }
        return $schemas;
    }
}
