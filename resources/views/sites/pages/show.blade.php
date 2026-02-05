@extends('layouts.app')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <nav class="sm:hidden" aria-label="Back">
                <a href="{{ route('sites.pages.index', request()->route('site')) }}" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                    <svg class="-ml-1 mr-1 h-5 w-5 flex-shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Back
                </a>
            </nav>
            <nav class="hidden sm:flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div class="flex">
                            <a href="{{ route('sites.pages.index', request()->route('site')) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Pages</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                            </svg>
                            <span class="ml-4 text-sm font-medium text-gray-500" id="breadcrumb-page-id">...</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="mt-2 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight" id="page-url">Loading...</h2>
        </div>
        <div class="mt-4 flex flex-shrink-0 md:ml-4 md:mt-0">
           <a href="#" target="_blank" id="external-link" class="ml-3 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Open URL</a>
        </div>
    </div>
</div>

<!-- Policy Verdict Section (PRIMARY) -->
<div class="mb-6 rounded-lg bg-white shadow p-6" id="policy-verdict-section">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900">SEO Policy Status</h3>
            <p class="mt-1 text-sm text-gray-500" id="policy-summary-text">Evaluating...</p>
        </div>
        <span id="policy-status-badge" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
            Pending
        </span>
    </div>
    <div class="mt-2 text-xs text-gray-400" id="policy-meta">
        <span id="policy-evaluated-at"></span>
    </div>
</div>

<!-- Tabs -->
<div x-data="{ tab: 'policy' }">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="tab = 'policy'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'policy', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'policy' }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">Policy Evaluation</button>
            <button @click="tab = 'content'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'content', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'content' }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">Content</button>
            <button @click="tab = 'structure'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'structure', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'structure' }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">Structure</button>
            <button @click="tab = 'technical'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'technical', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'technical' }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">Technical Details</button>
        </nav>
    </div>

    <!-- Policy Tab (Progressive Disclosure) -->
    <div x-show="tab === 'policy'" class="pt-6">
        <!-- Tier 1: Critical Issues (Auto-Expanded) -->
        <div id="tier-1-critical" class="mb-4" style="display:none;">
            <div class="rounded-md bg-red-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h4 class="text-sm font-medium text-red-800">Critical Issues</h4>
                        <div class="mt-2 text-sm text-red-700">
                            <ul id="critical-issues-list" class="list-disc pl-5 space-y-2"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tier 1: High Priority Issues (Auto-Expanded) -->
        <div id="tier-1-high" class="mb-4" style="display:none;">
            <div class="rounded-md bg-orange-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h4 class="text-sm font-medium text-orange-800">Important Issues</h4>
                        <div class="mt-2 text-sm text-orange-700">
                            <ul id="high-issues-list" class="list-disc pl-5 space-y-2"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tier 2: Warnings (Collapsed by Default) -->
        <div id="tier-2-warning" class="mb-4" style="display:none;">
            <details class="rounded-md bg-yellow-50 p-4">
                <summary class="cursor-pointer text-sm font-medium text-yellow-800 flex items-center">
                    <svg class="h-5 w-5 text-yellow-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <span id="warning-issues-count"></span> Recommended Improvements
                </summary>
                <div class="mt-2 text-sm text-yellow-700">
                    <ul id="warning-issues-list" class="list-disc pl-5 space-y-2"></ul>
                </div>
            </details>
        </div>

        <!-- Tier 3: Optional Improvements (Collapsed) -->
        <div id="tier-3-optional" class="mb-4" style="display:none;">
            <details class="rounded-md bg-blue-50 p-4">
                <summary class="cursor-pointer text-sm font-medium text-blue-800 flex items-center">
                    <svg class="h-5 w-5 text-blue-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <span id="optional-issues-count"></span> Optional Enhancements
                </summary>
                <div class="mt-2 text-sm text-blue-700">
                    <ul id="optional-issues-list" class="list-disc pl-5 space-y-2"></ul>
                </div>
            </details>
        </div>

        <!-- No Issues State -->
        <div id="no-issues-state" style="display:none;" class="rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">All SEO requirements met</p>
                    <p class="mt-1 text-sm text-green-700">This page follows all critical SEO best practices.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Tab -->
    <div x-show="tab === 'content'" class="pt-6" style="display: none;">
        <div class="grid grid-cols-1 gap-6">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Page Title (<code class="text-xs">&lt;title&gt;</code> tag)</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-words" id="meta-title">-</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Meta Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-words" id="meta-desc">-</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Main Heading (H1)</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-words" id="meta-h1">-</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Canonical URL</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-words" id="meta-canonical">-</dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Structure Tab -->
    <div x-show="tab === 'structure'" class="pt-6" style="display: none;">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
             <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Click Depth from Homepage</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="struct-depth">--</dd>
                </div>
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Inbound Links</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="struct-inbound">--</dd>
                </div>
            </div>
             <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Outbound Links</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="struct-outbound">--</dd>
                </div>
            </div>
        </div>
        <div class="mt-6 rounded-md bg-blue-50 p-4" id="orphan-alert" style="display:none;">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm text-blue-700"><strong>Orphan Page:</strong> This page has no internal inbound links from other pages on the site.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Details Tab (On-Demand) -->
    <div x-show="tab === 'technical'" class="pt-6" style="display: none;">
        <div class="rounded-lg bg-gray-50 p-4 mb-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Content Quality Score (Technical)</h4>
            <p class="text-3xl font-semibold text-gray-900" id="content-score">--</p>
            <p class="text-xs text-gray-500 mt-1">Weighted score based on content extraction analysis</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Raw Crawler Data</h4>
            <div class="bg-gray-50 p-4 rounded-lg overflow-auto">
                <pre class="text-xs text-gray-800" id="raw-json">Loading...</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    const PAGE_ID = "{{ request()->route('page') }}";
    
    // Human-friendly field translations
    const FIELD_LABELS = {
        'meta.title': 'Page Title (<code>&lt;title&gt;</code> tag)',
        'meta.description': 'Meta Description (shown in search results)',
        'h1_count': 'Main Heading (H1)',
        'depth_level': 'Click Depth from Homepage',
        'http_status_last': 'HTTP Response Status',
        'canonical_extracted': 'Canonical URL',
        'meta.robots': 'Robots Meta Tag',
        'analysis.canonical_status': 'Canonical Status',
        'structure.is_orphan': 'Internal Link Structure'
    };

    async function loadPage() {
        try {
            const page = await api(`/sites/${SITE_ID}/pages/${PAGE_ID}`);
            renderPage(page);
        } catch (e) {
            console.error(e);
            alert('Failed to load page data');
        }
    }

    function renderPage(page) {
        document.getElementById('page-url').textContent = page.path;
        document.getElementById('breadcrumb-page-id').textContent = page.id;
        document.getElementById('external-link').href = page.url;

        // Policy Verdict (PRIMARY)
        renderPolicyVerdict(page);

        // Content
        const analysis = page.analysis || {};
        document.getElementById('content-score').textContent = analysis.score ?? 'N/A';
        document.getElementById('meta-title').textContent = analysis.title || '-';
        document.getElementById('meta-desc').textContent = analysis.description || '-';
        document.getElementById('meta-h1').textContent = analysis.h1s ? analysis.h1s.join(', ') : '-';
        document.getElementById('meta-canonical').textContent = analysis.canonical || '-';

        // Structure
        const struct = page.structure || {};
        document.getElementById('struct-depth').textContent = struct.depth_from_home ?? '-';
        document.getElementById('struct-inbound').textContent = struct.inbound_count ?? 0;
        document.getElementById('struct-outbound').textContent = struct.outbound_count ?? 0;
        
        if (struct.is_orphan) {
            document.getElementById('orphan-alert').style.display = 'block';
        }

        // Raw
        document.getElementById('raw-json').textContent = JSON.stringify(page, null, 2);
    }

    function renderPolicyVerdict(page) {
        const policy = page.policy_summary || {};
        const violations = page.violations || [];
        const status = policy.status || 'PENDING';

        // Status Badge
        const statusConfig = {
            'FAIL': { bg: 'bg-orange-100', text: 'text-orange-800', label: 'Needs Attention' },
            'WARN': { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Could Be Better' },
            'PASS': { bg: 'bg-green-100', text: 'text-green-800', label: 'Healthy' },
            'PENDING': { bg: 'bg-gray-100', text: 'text-gray-600', label: 'Pending' }
        };
        
        const config = statusConfig[status] || statusConfig['PENDING'];
        const badge = document.getElementById('policy-status-badge');
        badge.className = `inline-flex items-center px-4 py-2 rounded-full text-sm font-medium ${config.bg} ${config.text}`;
        badge.textContent = config.label;

        // Summary Text
        const summaryText = document.getElementById('policy-summary-text');
        const violationsCount = policy.violations_count || 0;
        
        if (status === 'FAIL') {
            const criticalHigh = violations.filter(v => v.severity === 'CRITICAL' || v.severity === 'HIGH').length;
            summaryText.textContent = `This page has ${criticalHigh} ${criticalHigh === 1 ? 'issue' : 'issues'} that need attention.`;
        } else if (status === 'WARN') {
            summaryText.textContent = 'This page could be improved.';
        } else if (status === 'PASS') {
            const optimizations = violations.filter(v => v.severity === 'OPTIMIZATION' || v.severity === 'ADVISORY').length;
            summaryText.textContent = optimizations > 0 
                ? `This page meets all SEO requirements. (${optimizations} optional ${optimizations === 1 ? 'improvement' : 'improvements'} available)`
                : 'This page meets all SEO requirements.';
        } else {
            summaryText.textContent = 'Policy evaluation pending.';
        }

        // Evaluation metadata
        document.getElementById('policy-evaluated-at').textContent = policy.evaluated_at 
            ? `Evaluated at ${new Date(policy.evaluated_at).toLocaleString()}`
            : '';

        // Render violations by tier
        renderViolationsByTier(violations);
    }

    function renderViolationsByTier(violations) {
        const critical = violations.filter(v => v.severity === 'CRITICAL');
        const high = violations.filter(v => v.severity === 'HIGH');
        const warning = violations.filter(v => v.severity === 'WARNING');
        const optional = violations.filter(v => v.severity === 'OPTIMIZATION' || v.severity === 'ADVISORY');

        // Tier 1: Critical
        if (critical.length > 0) {
            document.getElementById('tier-1-critical').style.display = 'block';
            document.getElementById('critical-issues-list').innerHTML = critical.map(renderViolation).join('');
        }

        // Tier 1: High
        if (high.length > 0) {
            document.getElementById('tier-1-high').style.display = 'block';
            document.getElementById('high-issues-list').innerHTML = high.map(renderViolation).join('');
        }

        // Tier 2: Warning
        if (warning.length > 0) {
            document.getElementById('tier-2-warning').style.display = 'block';
            document.getElementById('warning-issues-count').textContent = warning.length;
            document.getElementById('warning-issues-list').innerHTML = warning.map(renderViolation).join('');
        }

        // Tier 3: Optional
        if (optional.length > 0) {
            document.getElementById('tier-3-optional').style.display = 'block';
            document.getElementById('optional-issues-count').textContent = optional.length;
            document.getElementById('optional-issues-list').innerHTML = optional.map(renderViolation).join('');
        }

        // No issues state
        if (violations.length === 0) {
            document.getElementById('no-issues-state').style.display = 'block';
        }
    }

    function renderViolation(violation) {
        const fieldLabel = FIELD_LABELS[violation.field] || violation.field;
        return `
            <li>
                <strong>${violation.explanation}</strong>
                <div class="text-xs mt-1">Affects: ${fieldLabel}</div>
            </li>
        `;
    }

    document.addEventListener('DOMContentLoaded', loadPage);
</script>
@endpush
