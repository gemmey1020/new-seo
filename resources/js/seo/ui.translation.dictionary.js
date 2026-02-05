/**
 * SEO Control System - UI Translation Dictionary
 * 
 * Centralized source of truth for all UI translations.
 * Enforces Progressive Disclosure Model (Strategy C).
 * 
 * FROZEN: Do not modify without updating GLOBAL_UI_TRANSLATION_GUIDE.md
 */

export const STATUS_LABELS = {
    'PASS': {
        label: 'Healthy',
        colorClass: 'bg-green-100 text-green-800',
        description: 'Meets all SEO requirements',
        icon: '✓'
    },
    'WARN': {
        label: 'Could Be Better',
        colorClass: 'bg-yellow-100 text-yellow-800',
        description: 'Some recommended improvements available',
        icon: '⚠'
    },
    'FAIL': {
        label: 'Needs Attention',
        colorClass: 'bg-orange-100 text-orange-800', // ORANGE, not red
        description: 'Important issues need to be addressed',
        icon: '!'
    },
    'PENDING': {
        label: 'Pending',
        colorClass: 'bg-gray-100 text-gray-600',
        description: 'Not yet evaluated',
        icon: '…'
    }
};

export const SEVERITY_LABELS = {
    'CRITICAL': {
        label: 'Urgent',
        colorClass: 'bg-red-100 text-red-800',
        borderClass: 'border-red-500',
        meaning: 'Blocks search engine indexing',
        priority: 1
    },
    'HIGH': {
        label: 'Important',
        colorClass: 'bg-orange-100 text-orange-800',
        borderClass: 'border-orange-500',
        meaning: 'Significantly affects SEO performance',
        priority: 2
    },
    'WARNING': {
        label: 'Recommended',
        colorClass: 'bg-yellow-100 text-yellow-800',
        borderClass: 'border-yellow-400',
        meaning: 'Best practice improvement',
        priority: 3
    },
    'OPTIMIZATION': {
        label: 'Optional',
        colorClass: 'bg-blue-100 text-blue-800',
        borderClass: 'border-blue-400',
        meaning: 'Nice-to-have enhancement',
        priority: 4
    },
    'ADVISORY': {
        label: 'Informational',
        colorClass: 'bg-gray-100 text-gray-600',
        borderClass: 'border-gray-300',
        meaning: 'For awareness only',
        priority: 5
    }
};

/**
 * Field Label Translations
 * Maps technical API field names to human-friendly labels
 */
export const FIELD_LABELS = {
    'meta.title': 'Page Title (<code>&lt;title&gt;</code> tag)',
    'meta.description': 'Meta Description (shown in search results)',
    'meta.robots': 'Robots Meta Tag',
    'h1_count': 'Main Heading (H1)',
    'h1s': 'Main Headings (H1)',
    'depth_level': 'Click Depth from Homepage',
    'depth_from_home': 'Click Depth from Homepage',
    'http_status_last': 'HTTP Response Status',
    'canonical_extracted': 'Canonical URL',
    'canonical': 'Canonical URL',
    'analysis.canonical_status': 'Canonical Status',
    'structure.is_orphan': 'Internal Link Structure',
    'content_bytes': 'Content Size',
    'inbound_count': 'Inbound Links',
    'outbound_count': 'Outbound Links'
};

/**
 * Terminology Translation Map
 * FORBIDDEN terms -> REQUIRED replacements
 */
export const TERMINOLOGY_MAP = {
    // Overview Dashboard
    'Health Score': 'Site Status',
    'Ghost Pages': 'Pages Returning Errors',
    'Zombie Pages': 'Pages Not Linked Internally',
    'Drift': 'System Stability',
    'Not Ready for v2': 'Observation Mode Active',

    // Structure View
    'Orphan': 'Not Linked Internally',
    'Orphans': 'Pages Not Linked Internally',
    'Depth': 'Click Depth',

    // General
    'Failed': 'Returned Errors',
    'Critical Audits': 'Urgent Issues',
    'Meta Density': 'Pages with Descriptions',
    'H1 Density': 'Pages with Headings',
    'Orphan Rate': 'Unlinked Pages'
};

/**
 * Human-friendly Status Messages
 * Based on status and optional context
 */
export const STATUS_MESSAGES = {
    'FAIL': (context) => {
        const count = context?.urgentCount || context?.violationsCount || 0;
        if (count === 0) return 'This page needs attention.';
        if (count === 1) return 'This page has 1 issue that needs attention.';
        return `This page has ${count} issues that need attention.`;
    },
    'WARN': (context) => {
        return 'This page could be improved.';
    },
    'PASS': (context) => {
        const optionalCount = context?.optionalCount || 0;
        if (optionalCount === 0) {
            return 'This page meets all SEO requirements.';
        }
        const plural = optionalCount === 1 ? 'improvement' : 'improvements';
        return `This page meets all SEO requirements. (${optionalCount} optional ${plural} available)`;
    },
    'PENDING': (context) => {
        return 'Policy evaluation pending.';
    }
};

/**
 * Site-level Status Messages
 */
export const SITE_STATUS_MESSAGES = {
    'HEALTHY': 'Your site looks good.',
    'NEEDS_ATTENTION': 'Several pages need attention.',
    'MOSTLY_GOOD': 'Most pages are healthy, with some improvements available.',
    'EVALUATING': 'Run diagnostics to check your site.'
};

/**
 * Policy Code to Human Message Map
 * Translates backend policy codes to user-facing messages
 */
export const POLICY_CODE_MESSAGES = {
    'CONTENT_TITLE_LENGTH': 'Page title length issue',
    'CONTENT_META_DESC': 'Meta description is missing or too short',
    'CONTENT_H1_COUNT': 'Main heading (H1) issue',
    'STRUCTURE_ORPHAN': 'Page is not linked internally',
    'STRUCTURE_DEPTH': 'Page is buried too deep in site structure',
    'INDEX_HTTP_STATUS': 'Page returned error (not accessible)',
    'INDEX_CANONICAL': 'Page points to different canonical URL',
    'INDEX_ROBOTS': 'Page is marked as "noindex"'
};

/**
 * Utility: Get translated field label
 */
export function getFieldLabel(fieldName) {
    return FIELD_LABELS[fieldName] || fieldName;
}

/**
 * Utility: Get human-friendly policy message
 */
export function getPolicyMessage(code) {
    return POLICY_CODE_MESSAGES[code] || 'SEO policy violation';
}

/**
 * Utility: Translate forbidden terminology
 */
export function translateTerminology(text) {
    let translated = text;
    Object.entries(TERMINOLOGY_MAP).forEach(([forbidden, required]) => {
        const regex = new RegExp(forbidden, 'gi');
        translated = translated.replace(regex, required);
    });
    return translated;
}
