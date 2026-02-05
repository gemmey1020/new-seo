/**
 * SEO Control System - UI Translation Helpers
 * 
 * Reusable rendering functions for consistent UI components.
 * All functions follow Progressive Disclosure Model (Strategy C).
 * 
 * Dependencies: ui.translation.dictionary.js
 */

import {
    STATUS_LABELS,
    SEVERITY_LABELS,
    STATUS_MESSAGES,
    getFieldLabel,
    getPolicyMessage
} from './ui.translation.dictionary.js';

/**
 * Render a status badge (PASS/WARN/FAIL/PENDING)
 * 
 * @param {string} status - Policy status
 * @param {object} context - Optional context (e.g., { size: 'sm', showIcon: true })
 * @returns {string} HTML string
 */
export function renderStatusBadge(status, context = {}) {
    const config = STATUS_LABELS[status] || STATUS_LABELS['PENDING'];
    const size = context.size || 'md';
    const showIcon = context.showIcon !== false;

    const sizeClasses = {
        'sm': 'px-2 py-0.5 text-xs',
        'md': 'px-2.5 py-0.5 text-xs',
        'lg': 'px-4 py-2 text-sm'
    };

    return `
        <span class="inline-flex items-center rounded-full font-medium ${config.colorClass} ${sizeClasses[size]}"
              title="${config.description}">
            ${showIcon ? config.icon + ' ' : ''}${config.label}
        </span>
    `;
}

/**
 * Render a severity badge (CRITICAL/HIGH/WARNING/etc.)
 * 
 * @param {string} severity - Violation severity
 * @param {object} context - Optional context
 * @returns {string} HTML string
 */
export function renderSeverityBadge(severity, context = {}) {
    const config = SEVERITY_LABELS[severity] || SEVERITY_LABELS['WARNING'];
    const showMeaning = context.showMeaning || false;

    return `
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${config.colorClass}"
              title="${config.meaning}">
            ${config.label}
            ${showMeaning ? `<span class="ml-1 text-xxs opacity-75">(${config.meaning})</span>` : ''}
        </span>
    `;
}

/**
 * Render a violation list item with full context
 * 
 * @param {object} violation - Violation object from API
 * @param {object} options - Rendering options
 * @returns {string} HTML string
 */
export function renderViolationItem(violation, options = {}) {
    const showField = options.showField !== false;
    const showMeasured = options.showMeasured !== false;

    const explanation = violation.explanation || getPolicyMessage(violation.code);
    const fieldLabel = getFieldLabel(violation.field);

    // Attempt to extract measured value if available in violation metadata
    let measuredValueHtml = '';
    if (showMeasured && violation.measured_value !== undefined) {
        measuredValueHtml = `
            <div class="text-xs text-gray-400 mt-1">
                Current: ${violation.measured_value}
                ${violation.expected_value ? ` | Expected: ${violation.expected_value}` : ''}
            </div>
        `;
    }

    return `
        <li class="py-2">
            <strong class="text-sm text-gray-900">${explanation}</strong>
            ${showField ? `<div class="text-xs text-gray-500 mt-1">Affects: ${fieldLabel}</div>` : ''}
            ${measuredValueHtml}
        </li>
    `;
}

/**
 * Group violations by severity tier
 * Returns object with critical, high, warning, optional arrays
 * 
 * @param {array} violations - Array of violation objects
 * @returns {object} Grouped violations
 */
export function groupViolationsBySeverity(violations) {
    return {
        critical: violations.filter(v => v.severity === 'CRITICAL'),
        high: violations.filter(v => v.severity === 'HIGH'),
        warning: violations.filter(v => v.severity === 'WARNING'),
        optional: violations.filter(v => ['OPTIMIZATION', 'ADVISORY'].includes(v.severity))
    };
}

/**
 * Format a severity breakdown summary
 * Example: "3 violations: 1 urgent, 2 optional"
 * 
 * @param {array} violations - Array of violation objects
 * @returns {string} Human-readable summary
 */
export function formatSeverityBreakdown(violations) {
    if (!violations || violations.length === 0) {
        return '—';
    }

    const grouped = groupViolationsBySeverity(violations);
    const parts = [];

    if (grouped.critical.length > 0) {
        parts.push(`${grouped.critical.length} urgent`);
    }
    if (grouped.high.length > 0) {
        parts.push(`${grouped.high.length} important`);
    }
    if (grouped.warning.length > 0) {
        parts.push(`${grouped.warning.length} recommended`);
    }
    if (grouped.optional.length > 0) {
        parts.push(`${grouped.optional.length} optional`);
    }

    const total = violations.length;
    const plural = total === 1 ? 'issue' : 'issues';

    if (parts.length === 0) {
        return `${total} ${plural}`;
    }

    return `${total} ${plural}: ${parts.join(', ')}`;
}

/**
 * Render a status message with context
 * 
 * @param {string} status - Policy status
 * @param {object} context - Context object (urgentCount, optionalCount, etc.)
 * @returns {string} Human-friendly message
 */
export function renderStatusMessage(status, context = {}) {
    const messageFn = STATUS_MESSAGES[status] || STATUS_MESSAGES['PENDING'];
    return messageFn(context);
}

/**
 * Calculate priority issues count (CRITICAL + HIGH only)
 * 
 * @param {array} violations - Array of violation objects
 * @returns {number} Count of priority issues
 */
export function getPriorityIssuesCount(violations) {
    if (!violations) return 0;
    return violations.filter(v =>
        v.severity === 'CRITICAL' || v.severity === 'HIGH'
    ).length;
}

/**
 * Render a progressive disclosure section
 * Creates a collapsible <details> element
 * 
 * @param {object} config - { title, count, items, severity, defaultOpen }
 * @returns {string} HTML string
 */
export function renderProgressiveSection(config) {
    const { title, count, items, severity, defaultOpen = false } = config;
    const severityConfig = SEVERITY_LABELS[severity] || SEVERITY_LABELS['WARNING'];

    const openAttr = defaultOpen ? 'open' : '';
    const colorClass = severityConfig.colorClass.replace('text-', 'bg-').replace('100', '50');
    const textColor = severityConfig.colorClass.split(' ')[1];

    return `
        <details ${openAttr} class="rounded-md ${colorClass} p-4 mb-4">
            <summary class="cursor-pointer text-sm font-medium ${textColor} flex items-center">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    ${getSeverityIcon(severity)}
                </svg>
                ${count !== undefined ? count + ' ' : ''}${title}
            </summary>
            <div class="mt-2 text-sm ${textColor.replace('800', '700')}">
                <ul class="list-disc pl-5 space-y-2">
                    ${items}
                </ul>
            </div>
        </details>
    `;
}

/**
 * Get SVG path for severity icon
 * 
 * @param {string} severity
 * @returns {string} SVG path
 */
function getSeverityIcon(severity) {
    const icons = {
        'CRITICAL': '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />',
        'HIGH': '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />',
        'WARNING': '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />',
        'OPTIMIZATION': '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />'
    };
    return icons[severity] || icons['WARNING'];
}

/**
 * Render site-level status summary
 * Aggregates page-level statuses into site verdict
 * 
 * @param {object} stats - { total, fail, warn, pass }
 * @returns {object} { status, message, badge }
 */
export function renderSiteStatus(stats) {
    const { total, fail = 0, warn = 0, pass = 0 } = stats;

    if (total === 0) {
        return {
            status: 'PENDING',
            message: 'Run diagnostics to check your site.',
            badge: renderStatusBadge('PENDING', { size: 'lg' })
        };
    }

    const failRate = fail / total;
    const warnRate = warn / total;

    if (failRate > 0.3) {
        // More than 30% need attention
        return {
            status: 'FAIL',
            message: `${fail} ${fail === 1 ? 'page needs' : 'pages need'} attention`,
            badge: renderStatusBadge('FAIL', { size: 'lg' })
        };
    } else if (fail > 0 || warnRate > 0.3) {
        // Some issues exist
        return {
            status: 'WARN',
            message: `${fail + warn} ${fail + warn === 1 ? 'page could' : 'pages could'} be improved`,
            badge: renderStatusBadge('WARN', { size: 'lg' })
        };
    } else {
        // Mostly healthy
        return {
            status: 'PASS',
            message: 'Your site looks good',
            badge: renderStatusBadge('PASS', { size: 'lg' })
        };
    }
}

/**
 * Format percentage for display
 * 
 * @param {number} value - Decimal value (0.45 = 45%)
 * @param {object} context - { label, invert }
 * @returns {string} Human-friendly text
 */
export function formatPercentage(value, context = {}) {
    const percentage = Math.round(value * 100);
    const label = context.label || 'of pages';

    return `${percentage}% ${label}`;
}
