@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Overview</h1>
    <div class="flex items-center gap-3">
        <button id="run-diagnostics-btn" onclick="runDiagnostics()" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Run Diagnostics
        </button>
        <!-- Site Status (Policy-First UI) -->
        <div id="site-status-container" class="flex items-center gap-3">
            <span class="text-sm text-gray-500">Site Status:</span>
            <span id="site-status-badge"></span>
            <p id="site-status-message" class="text-sm text-gray-700"></p>
        </div>
    </div>
</div>

<!-- v1.1 Intelligence Grid -->
<div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-3">
    
    <!-- 1. Stability & Compliance -->
    <div class="rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Health Dimensions</h3>
        </div>
        <div class="px-4 py-5 sm:p-6 space-y-4" id="dimensions-container">
            <div class="text-sm text-gray-500">Loading intelligence...</div>
        </div>
    </div>

    <!-- 2. Drift Monitor (v1.1) -->
    <div class="rounded-lg bg-white shadow">
         <div class="border-b border-gray-200 px-4 py-4 sm:px-6 flex justify-between items-center">
            <h3 class="text-base font-semibold leading-6 text-gray-900">System Stability</h3>
            <span id="drift-status-badge" class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Checking...</span>
        </div>
        <div class="px-4 py-5 sm:p-6" id="drift-container">
             <div class="text-sm text-gray-500">Comparing Sitemap vs Reality...</div>
        </div>
    </div>

    <!-- 3. Readiness Verdict (v1.1) -->
    <div class="rounded-lg bg-white shadow bg-gradient-to-br from-white to-gray-50">
        <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Authority Readiness</h3>
        </div>
        <div class="px-4 py-5 sm:p-6" id="readiness-container">
             <div class="text-sm text-gray-500">Evaluating...</div>
        </div>
    </div>
</div>

<div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
    <!-- Recent Audits -->
    <div class="rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Recent Critical Issues</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
             <ul id="recent-audits-list" class="space-y-4">
                 <li class="text-sm text-gray-500">Loading...</li>
             </ul>
             <div class="mt-4">
                 <a href="{{ route('sites.audits.index', request()->route('site')) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">View all audits &rarr;</a>
             </div>
        </div>
    </div>

    <!-- Crawl Status -->
    <div class="rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Latest Crawl</h3>
        </div>
        <div class="px-4 py-5 sm:p-6" id="crawl-status-box">
             <p class="text-sm text-gray-500">Checking...</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function loadDashboard() {
        // Only load safe, non-computationally expensive data by default
        const [audits, crawlRuns] = await Promise.all([
            api(`/sites/${SITE_ID}/audits?per_page=5`),
            api(`/sites/${SITE_ID}/crawl/runs?per_page=1`) 
        ]);

        renderAudits(audits);
        renderCrawl(crawlRuns);
        
        // Setup initial state for diagnostics
        document.getElementById('site-status-badge').innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pending</span>';
        document.getElementById('site-status-message').textContent = 'Run diagnostics to check your site.';
        document.getElementById('drift-status-badge').innerHTML = '<span class="text-gray-400">Waiting...</span>';
        document.getElementById('dimensions-container').innerHTML = '<div class="text-sm text-gray-500 italic">Run diagnostics to view health data.</div>';
        document.getElementById('readiness-container').innerHTML = '<div class="text-sm text-gray-500 italic">Run diagnostics to view authority status.</div>';
    }

    async function runDiagnostics() {
        const btn = document.getElementById('run-diagnostics-btn');
        if(btn) { btn.disabled = true; btn.textContent = 'Running...'; }

        try {
            const [health, drift, readiness] = await Promise.all([
                api(`/sites/${SITE_ID}/health`),     // v1.1
                api(`/sites/${SITE_ID}/health/drift`), // v1.1
                api(`/sites/${SITE_ID}/health/readiness`) // v1.1
            ]);

            renderHealth(health);
            renderDrift(drift);
            renderReadiness(readiness);
        } catch (e) {
            console.error('Diagnostics failed', e);
            alert('Diagnostics failed to run.');
        } finally {
            if(btn) { btn.disabled = false; btn.textContent = 'Run Diagnostics'; }
        }
    }

    function renderHealth(data) {
        // Site Status Badge (Policy-First UI)
        const statusBadge = document.getElementById('site-status-badge');
        const statusMessage = document.getElementById('site-status-message');
        
        // Determine status based on score thresholds
        let status, message, badgeClass;
        if(data.score >= 80) {
            status = 'Healthy';
            message = 'Your site looks good';
            badgeClass = 'bg-green-100 text-green-800';
        } else if(data.score >= 60) {
            status = 'Could Be Better';
            message = 'Some pages could be improved';
            badgeClass = 'bg-yellow-100 text-yellow-800';
        } else {
            status = 'Needs Attention';
            message = 'Several pages need attention';
            badgeClass = 'bg-orange-100 text-orange-800'; // ORANGE, not red
        }
        
        statusBadge.innerHTML = `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">${status}</span>`;
        statusMessage.textContent = message;

        // Dimensions breakdown - Human-friendly summaries with progressive disclosure
        const dim = data.dimensions;
        
        // Human-readable dimension summaries
        const contentSummary = dim.content.score >= 70 
            ? `${(dim.content.metrics.meta_density * 100).toFixed(0)}% of pages have proper metadata` 
            : 'Several pages are missing important metadata';
            
        const stabilitySummary = dim.stability.score >= 70
            ? 'Pages are responding correctly'
            : 'Some pages returned errors during crawling';
            
        const structureSummary = dim.structure.metrics.orphan_rate > 0.1
            ? `${(dim.structure.metrics.orphan_rate * 100).toFixed(0)}% of pages are not linked internally`
            : 'Internal linking looks good';

        document.getElementById('dimensions-container').innerHTML = `
            <div class="space-y-3">
                <div class="p-3 bg-gray-50 rounded">
                    <h4 class="font-medium text-gray-900">Content Quality</h4>
                    <p class="text-sm text-gray-600 mt-1">${contentSummary}</p>
                </div>
                <div class="p-3 bg-gray-50 rounded">
                    <h4 class="font-medium text-gray-900">Technical Accessibility</h4>
                    <p class="text-sm text-gray-600 mt-1">${stabilitySummary}</p>
                </div>
                <div class="p-3 bg-gray-50 rounded">
                    <h4 class="font-medium text-gray-900">Site Structure</h4>
                    <p class="text-sm text-gray-600 mt-1">${structureSummary}</p>
                </div>
                <details class="mt-4">
                    <summary class="cursor-pointer text-xs text-gray-500 hover:text-gray-700">Show technical metrics</summary>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-600">
                        <div><strong>Content Score:</strong> ${dim.content.score}</div>
                        <div><strong>Stability Score:</strong> ${dim.stability.score}</div>
                        <div><strong>Structure Score:</strong> ${dim.structure.score}</div>
                        <div><strong>Success Rate:</strong> ${(dim.stability.metrics.success_rate * 100).toFixed(0)}%</div>
                    </div>
                </details>
            </div>
            <div class="text-right text-xs text-gray-400 pt-2">Generated: ${new Date(data.generated_at).toLocaleTimeString()}</div>
        `;
    }

    function renderDrift(data) {
        const badge = document.getElementById('drift-status-badge');
        badge.textContent = data.status;
        
        let color = 'bg-gray-100 text-gray-600';
        if(data.status === 'CRITICAL') color = 'bg-red-100 text-red-800';
        else if(data.status === 'DRIFTING') color = 'bg-yellow-100 text-yellow-800';
        else color = 'bg-green-100 text-green-800';
        
        badge.className = `inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${color}`;

        const inds = data.indicators;
        
        // v1.2 State Drift
        let stateDriftHtml = '';
        if (inds.state) {
            stateDriftHtml = `
                <div class="flex justify-between items-center">
                    <dt class="text-sm font-medium text-gray-500">State Drift (HTTP!=200)</dt>
                    <dd class="text-sm font-bold ${inds.state.severity === 'SAFE' ? 'text-gray-900' : 'text-red-600'}">
                        ${inds.state.count}
                    </dd>
                </div>
            `;
        }

        document.getElementById('drift-container').innerHTML = `
            <dl class="space-y-3">
                <div class="flex justify-between items-center">
                    <dt class="text-sm font-medium text-gray-500">Pages Returning Errors</dt>
                    <dd class="text-xs text-gray-400">HTTP 404 or other error codes</dd>
                    <dd class="text-sm font-bold ${inds.ghost.severity === 'CRITICAL' ? 'text-orange-600' : 'text-gray-900'}">
                        ${inds.ghost.count}
                    </dd>
                </div>
                ${stateDriftHtml}
                 <div class="flex justify-between items-center">
                    <dt class="text-sm font-medium text-gray-500">Pages Not Linked Internally</dt>
                    <dd class="text-xs text-gray-400">No inbound links from other pages</dd>
                    <dd class="text-sm font-bold ${inds.zombie.severity === 'WARNING' ? 'text-yellow-600' : 'text-gray-900'}">
                        ${inds.zombie.count}
                    </dd>
                </div>
            </dl>
            <div class="mt-4 p-2 bg-blue-50 border-l-4 border-blue-400 text-blue-800 text-xs rounded">
                <strong>Why this matters:</strong> These gaps between expected and actual site structure can affect search engine discovery.
            </div>
        `;
    }

    function renderReadiness(data) {
        const container = document.getElementById('readiness-container');
        if(data.ready) {
             container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-2 text-center">
                    <svg class="h-10 w-10 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h4 class="text-lg font-bold text-gray-900">System Ready</h4>
                    <p class="text-sm text-gray-500">Authority Mode available.</p>
                </div>
            `;
        } else {
             container.innerHTML = `
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                             <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Observation Mode Active</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Policy evaluation is running in read-only mode. Continue building confidence in the data before enabling authority features.</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    function renderAudits(audits) {
        const auditContainer = document.getElementById('recent-audits-list');
        if (audits.data.length) {
            let html = '';
            audits.data.forEach(audit => {
                html += `<li class="flex justify-between border-b pb-2 last:border-0 items-start">
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 uppercase">${audit.severity}</span>
                            <span class="ml-3 text-sm text-gray-700 flex-1 truncate" title="${audit.description}">${audit.description || 'Issue detected'}</span>
                         </li>`;
            });
            auditContainer.innerHTML = html;
        } else {
            auditContainer.innerHTML = '<li class="text-green-600 text-sm">No recent critical issues found.</li>';
        }
    }

    function renderCrawl(crawlRuns) {
        const crawlBox = document.getElementById('crawl-status-box');
         if (crawlRuns.data.length) {
            const run = crawlRuns.data[0];
            crawlBox.innerHTML = `
                <dl class="grid grid-cols-2 gap-4">
                    <div><dt class="text-xs text-gray-500">Status</dt><dd class="font-bold text-sm">${run.status}</dd></div>
                    <div><dt class="text-xs text-gray-500">Mode</dt><dd class="text-sm">${run.mode}</dd></div>
                    <div><dt class="text-xs text-gray-500">Pages</dt><dd class="text-sm font-mono">${run.pages_crawled}</dd></div>
                    <div><dt class="text-xs text-gray-500">Errors</dt><dd class="text-sm font-mono text-red-600">${run.errors_count}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-gray-500">Last Run</dt><dd class="text-sm">${new Date(run.started_at).toLocaleString()}</dd></div>
                </dl>
                <div class="mt-4">
                     <a href="{{ route('sites.crawl.index', request()->route('site')) }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">View crawl details &rarr;</a>
                </div>
            `;
        } else {
            crawlBox.innerHTML = `
                <p class="text-sm text-gray-500">No crawl history.</p>
                <div class="mt-4">
                     <span class="text-sm text-gray-400">Go to Crawl Manager to start.</span>
                </div>
            `;
        }
    }

    // C.3 - Show welcome toast for newly created sites
    function showWelcomeToast() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('welcome') === '1') {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 px-4 py-3 rounded shadow-lg z-50 bg-green-500 text-white transition-opacity duration-300';
            toast.innerHTML = '🎉 Site created successfully! Bootstrap data initialized.';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    showWelcomeToast();
    loadDashboard();
</script>
@endpush
@endsection
