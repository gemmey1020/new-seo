@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Overview</h1>
    <!-- Health Score Badge (v1.1) -->
    <div id="health-score-container" class="hidden flex items-center gap-2">
        <span class="text-xs text-gray-500 uppercase tracking-wider">Health Score</span>
        <div id="health-score-badge" class="flex items-center justify-center rounded-full bg-gray-200 px-4 py-1 text-lg font-bold shadow-sm">
            --
        </div>
        <div id="health-grade-badge" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-600 text-sm font-bold text-white shadow-sm">
            -
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
            <h3 class="text-base font-semibold leading-6 text-gray-900">Drift Monitor</h3>
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
        const [health, drift, readiness, audits, crawlRuns] = await Promise.all([
            api(`/sites/${SITE_ID}/health`),     // v1.1
            api(`/sites/${SITE_ID}/health/drift`), // v1.1
            api(`/sites/${SITE_ID}/health/readiness`), // v1.1
            api(`/sites/${SITE_ID}/audits?per_page=5`),
            api(`/sites/${SITE_ID}/crawl/runs?per_page=1`) 
        ]);

        renderHealth(health);
        renderDrift(drift);
        renderReadiness(readiness);
        renderAudits(audits);
        renderCrawl(crawlRuns);
    }

    function renderHealth(data) {
        // Badge
        document.getElementById('health-score-container').classList.remove('hidden');
        const scoreBadge = document.getElementById('health-score-badge');
        const gradeBadge = document.getElementById('health-grade-badge');
        
        scoreBadge.textContent = data.score;
        gradeBadge.textContent = data.grade;

        // Color Logic
        let colorClass = 'bg-red-100 text-red-800';
        if(data.score >= 90) colorClass = 'bg-green-100 text-green-800';
        else if(data.score >= 70) colorClass = 'bg-yellow-100 text-yellow-800';
        
        scoreBadge.className = `flex items-center justify-center rounded-full px-4 py-1 text-lg font-bold shadow-sm ${colorClass}`;

        // Dimensions breakdown (v1.2 Enhanced)
        const dim = data.dimensions;
        
        // History Bars
        let historyHtml = '';
        if (data.history && data.history.length) {
            historyHtml = `<div class="mt-4 pt-4 border-t border-gray-100"><div class="text-xs text-gray-400 mb-1">Stability Trend (Last 5 Runs)</div><div class="flex items-end gap-1 h-8">`;
            data.history.reverse().forEach(run => {
                let h = Math.max(10, (run.score / 100) * 32); 
                let col = run.score >= 70 ? 'bg-green-300' : 'bg-red-300';
                historyHtml += `<div class="w-4 ${col} rounded-t" style="height:${h}px" title="${new Date(run.date).toLocaleDateString()}: ${run.score}"></div>`;
            });
            historyHtml += `</div></div>`;
        }

        document.getElementById('dimensions-container').innerHTML = `
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <div class="text-xs text-gray-500 uppercase">Stability</div>
                    <div class="text-xl font-bold ${dim.stability.score < 70 ? 'text-red-600' : 'text-gray-900'}">${dim.stability.score}</div>
                    <div class="text-xs text-gray-400">Success: ${(dim.stability.metrics.success_rate * 100).toFixed(0)}%</div>
                    <div class="text-xs text-gray-400">Latency: ${dim.stability.metrics.latency_avg_ms}ms</div>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <div class="text-xs text-gray-500 uppercase">Compliance</div>
                    <div class="text-xl font-bold ${dim.compliance.score < 70 ? 'text-red-600' : 'text-gray-900'}">${dim.compliance.score}</div>
                    <div class="text-xs text-gray-400">Critical: ${dim.compliance.metrics.critical_audits}</div>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <div class="text-xs text-gray-500 uppercase">Content</div>
                    <div class="text-xl font-bold ${dim.content.score < 70 ? 'text-red-600' : 'text-gray-900'}">${dim.content.score}</div>
                    <div class="text-xs text-gray-400">Meta: ${(dim.content.metrics.meta_density * 100).toFixed(0)}%</div>
                    <div class="text-xs text-gray-400">H1: ${(dim.content.metrics.h1_density * 100).toFixed(0)}%</div>
                </div>
                <div class="p-3 bg-gray-50 rounded border border-gray-100">
                    <div class="text-xs text-gray-500 uppercase">Structure</div>
                    <div class="text-xl font-bold ${dim.structure.score < 70 ? 'text-red-600' : 'text-gray-900'}">${dim.structure.score}</div>
                    <div class="text-xs text-gray-400">Orphans: ${(dim.structure.metrics.orphan_rate * 100).toFixed(0)}%</div>
                </div>
            </div>
            ${historyHtml}
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
                    <dt class="text-sm font-medium text-gray-500">Ghost Pages (404s)</dt>
                    <dd class="text-sm font-bold ${inds.ghost.severity === 'CRITICAL' ? 'text-red-600' : 'text-gray-900'}">
                        ${inds.ghost.count}
                    </dd>
                </div>
                ${stateDriftHtml}
                 <div class="flex justify-between items-center">
                    <dt class="text-sm font-medium text-gray-500">Zombie Pages (Orphans)</dt>
                    <dd class="text-sm font-bold ${inds.zombie.severity === 'WARNING' ? 'text-yellow-600' : 'text-gray-900'}">
                        ${inds.zombie.count}
                    </dd>
                </div>
            </dl>
            <div class="mt-4 p-2 bg-blue-50 text-blue-800 text-xs rounded">
                <strong>Drift:</strong> The gap between your Sitemap (Intent) and Reality (Crawl).
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
             const blockers = data.blockers.map(b => `<li class="text-red-700">${b}</li>`).join('');
             container.innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                             <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Not Ready for v2</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    ${blockers}
                                </ul>
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
