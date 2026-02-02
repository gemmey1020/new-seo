@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Overview</h1>
    <div id="health-score-badge" class="rounded-full bg-gray-200 px-4 py-1 text-sm font-bold">Health: --</div>
</div>

<div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat Cards -->
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Total Pages</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="stat-total-pages">-</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Open Audits</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600" id="stat-open-audits">-</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Crawled Pages</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="stat-crawled-pages">-</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Pending Tasks</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600" id="stat-pending-tasks">-</dd>
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
        // Parallel fetch for density
        const [pages, audits, tasks, crawlRuns] = await Promise.all([
            api(`/sites/${SITE_ID}/pages?per_page=1`), // Just to get total
            api(`/sites/${SITE_ID}/audits?per_page=5`), // Recent
            api(`/sites/${SITE_ID}/tasks?per_page=1`), // Count logic (pseudo)
            api(`/sites/${SITE_ID}/crawl/runs?per_page=1`) // Latest
        ]);

        // Stats
        document.getElementById('stat-total-pages').textContent = pages.total;
        document.getElementById('stat-open-audits').textContent = audits.total; // Rough proxy for open
        // Task count (need real stats endpoint in future, using total list for MVP)
        document.getElementById('stat-pending-tasks').textContent = tasks.total;

        // Audits List
        const auditContainer = document.getElementById('recent-audits-list');
        if (audits.data.length) {
            let html = '';
            audits.data.forEach(audit => {
                html += `<li class="flex justify-between border-b pb-2 last:border-0">
                            <span class="font-medium text-red-600">[${audit.severity}]</span>
                            <span class="truncate ml-2 text-gray-700 flex-1">${audit.description || 'Issue detected'}</span>
                         </li>`;
            });
            auditContainer.innerHTML = html;
        } else {
            auditContainer.innerHTML = '<li class="text-green-600">No recent issues found.</li>';
        }

        // Crawl Status
        const crawlBox = document.getElementById('crawl-status-box');
        if (crawlRuns.data.length) {
            const run = crawlRuns.data[0];
            document.getElementById('stat-crawled-pages').textContent = run.pages_crawled;
            
            crawlBox.innerHTML = `
                <dl class="grid grid-cols-2 gap-4">
                    <div><dt class="text-xs text-gray-500">Status</dt><dd class="font-bold">${run.status}</dd></div>
                    <div><dt class="text-xs text-gray-500">Mode</dt><dd>${run.mode}</dd></div>
                    <div><dt class="text-xs text-gray-500">Pages</dt><dd>${run.pages_crawled}</dd></div>
                    <div><dt class="text-xs text-gray-500">Errors</dt><dd class="text-red-500">${run.errors_count}</dd></div>
                    <div class="col-span-2"><dt class="text-xs text-gray-500">Last Run</dt><dd>${new Date(run.started_at).toLocaleString()}</dd></div>
                </dl>
                <div class="mt-4">
                     <button onclick="triggerCrawl()" class="inline-flex items-center rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Run New Crawl</button>
                </div>
            `;
        } else {
            crawlBox.innerHTML = `
                <p class="text-gray-500">No crawl history.</p>
                <div class="mt-4">
                     <button onclick="triggerCrawl()" class="inline-flex items-center rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Start First Crawl</button>
                </div>
            `;
             document.getElementById('stat-crawled-pages').textContent = "0";
        }
        
        // Dummy Health Score Calculation (MVP)
        let score = 100 - (audits.total * 2); // Simple calc
        if(score < 0) score = 0;
        const hb = document.getElementById('health-score-badge');
        hb.textContent = `Health: ${score}`;
        if(score > 80) hb.classList.add('bg-green-100', 'text-green-800');
        else if(score > 50) hb.classList.add('bg-yellow-100', 'text-yellow-800');
        else hb.classList.add('bg-red-100', 'text-red-800');
    }

    async function triggerCrawl() {
        if(!confirm('Start a new crawl?')) return;
        try {
            await api(`/sites/${SITE_ID}/crawl/run`, 'POST');
            alert('Crawl started!');
            loadDashboard(); // Refresh
        } catch(e) {
            alert('Failed to start crawl.');
        }
    }

    loadDashboard();
</script>
@endpush
@endsection
