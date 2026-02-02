@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-semibold text-gray-900">Crawl Monitor</h1>

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
    async function loadData() {
        // Runs
        const runs = await api(`/sites/${SITE_ID}/crawl/runs`);
        document.getElementById('runs-list').innerHTML = runs.data.map(run => `
            <li class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow">
                <div class="flex w-full items-center justify-between space-x-6 p-6">
                    <div class="flex-1 truncate">
                        <div class="flex items-center space-x-3">
                            <h3 class="truncate text-sm font-medium text-gray-900">Run #${run.id}</h3>
                            <span class="inline-block flex-shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">${run.status}</span>
                        </div>
                        <p class="mt-1 truncate text-sm text-gray-500">${run.pages_crawled} Pages</p>
                    </div>
                </div>
            </li>
        `).join('');

        // Logs
        const logs = await api(`/sites/${SITE_ID}/crawl/logs`);
        document.getElementById('logs-table').innerHTML = logs.data.map(log => `
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900">${log.status_code}</td>
                <td class="px-3 py-4 text-sm text-gray-500 truncate max-w-xs" title="${log.final_url || ''}">${log.final_url || '-'}</td>
                <td class="px-3 py-4 text-sm text-gray-500">${log.response_ms}ms</td>
                <td class="px-3 py-4 text-sm text-gray-500">${new Date(log.crawled_at).toLocaleTimeString()}</td>
            </tr>
        `).join('');
    }
    loadData();
    setInterval(loadData, 10000); // Polling
</script>
@endpush
@endsection
