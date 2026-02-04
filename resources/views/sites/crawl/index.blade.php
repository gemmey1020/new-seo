@extends('layouts.app')

@section('content')
<!-- D.1 - Crawl Trigger UI -->
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Crawl Monitor</h1>
        <p class="mt-2 text-sm text-gray-700">View crawl runs and live crawl logs for this site.</p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <button id="start-crawl-btn" onclick="startCrawl()" class="rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            Start New Crawl
        </button>
    </div>
</div>

<div class="mt-8">
    <h3 class="text-lg font-medium leading-6 text-gray-900">Recent Runs</h3>
    <ul id="runs-list" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <!-- JS -->
    </ul>
</div>

<div class="mt-8 border-t pt-8">
    <h3 class="text-lg font-medium leading-6 text-gray-900">Live Logs</h3>
    <div class="mt-4 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Status</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">URL</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Time</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Recorded</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white" id="logs-table"></tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    // D.1 - Toast notification for crawl actions
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-4 py-3 rounded shadow-lg z-50 transition-opacity duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    async function loadData() {
        // Runs
        const runs = await api(`/sites/${SITE_ID}/crawl/runs`);
        const runsList = document.getElementById('runs-list');
        
        if (!runs.data || runs.data.length === 0) {
            runsList.innerHTML = `<li class="col-span-full text-center py-8 text-gray-500">No crawl runs yet. Click "Start New Crawl" to begin.</li>`;
        } else {
            runsList.innerHTML = runs.data.map(run => `
                <li class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow">
                    <div class="flex w-full items-center justify-between space-x-6 p-6">
                        <div class="flex-1 truncate">
                            <div class="flex items-center space-x-3">
                                <h3 class="truncate text-sm font-medium text-gray-900">Run #${run.id}</h3>
                                <span class="inline-block flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ${
                                    run.status === 'completed' ? 'bg-green-100 text-green-800' :
                                    run.status === 'running' ? 'bg-blue-100 text-blue-800' :
                                    run.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    'bg-gray-100 text-gray-800'
                                }">${run.status}</span>
                            </div>
                            <p class="mt-1 truncate text-sm text-gray-500">${run.pages_crawled} Pages</p>
                        </div>
                    </div>
                </li>
            `).join('');
        }

        // Logs
        const logs = await api(`/sites/${SITE_ID}/crawl/logs`);
        const logsTable = document.getElementById('logs-table');
        
        if (!logs.data || logs.data.length === 0) {
            logsTable.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-gray-500">No crawl logs yet.</td></tr>`;
        } else {
            logsTable.innerHTML = logs.data.map(log => `
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900">${log.status_code}</td>
                    <td class="px-3 py-4 text-sm text-gray-500 truncate max-w-xs" title="${log.final_url || ''}">${log.final_url || '-'}</td>
                    <td class="px-3 py-4 text-sm text-gray-500">${log.response_ms}ms</td>
                    <td class="px-3 py-4 text-sm text-gray-500">${new Date(log.crawled_at).toLocaleTimeString()}</td>
                </tr>
            `).join('');
        }
    }

    // D.1 - Start Crawl action with feedback
    async function startCrawl() {
        if(!confirm('Start a new crawl for this site?')) return;
        
        const btn = document.getElementById('start-crawl-btn');
        const originalText = btn.textContent;
        btn.textContent = 'Starting...';
        btn.disabled = true;
        
        try {
            await api(`/sites/${SITE_ID}/crawl/runs`, 'POST', {
                mode: 'full',
                user_agent: 'SEO-OS-Bot/1.0'
            });
            showToast('Crawl started! Monitor progress below.', 'success');
            loadData();
        } catch(e) {
            showToast(e.message || 'Failed to start crawl.', 'error');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }

    loadData();
    setInterval(loadData, 10000); // Polling
</script>
@endpush
@endsection
