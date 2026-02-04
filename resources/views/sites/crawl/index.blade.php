@extends('layouts.app')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Crawl Monitor</h1>
        <p class="mt-2 text-sm text-gray-700">Timeline of crawler executions and their results.</p>
    </div>
</div>

<div class="mt-8 flex flex-col">
    <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Run ID</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Stats</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                        </tr>
                    </thead>
                    <tbody id="runs-table-body" class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td colspan="4" class="py-4 text-center text-sm text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadRuns() {
        try {
            const res = await api(`/sites/${SITE_ID}/crawl/runs`);
            renderRuns(res.data);
        } catch (e) {
            console.error(e);
            document.getElementById('runs-table-body').innerHTML = '<tr><td colspan="4" class="py-4 text-center text-sm text-red-500">Error loading runs.</td></tr>';
        }
    }

    function renderRuns(runs) {
        const tbody = document.getElementById('runs-table-body');
        if (runs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-sm text-gray-500">No crawl runs found.</td></tr>';
            return;
        }

        tbody.innerHTML = runs.map(run => {
            const statusColors = {
                'completed': 'bg-green-100 text-green-800',
                'failed': 'bg-red-100 text-red-800',
                'running': 'bg-blue-100 text-blue-800',
                'pending': 'bg-gray-100 text-gray-800'
            };
            const badgeClass = statusColors[run.status] || 'bg-gray-100 text-gray-800';

            return `
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                        #${run.id}
                        <div class="text-xs text-gray-500 uppercase">${run.mode}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">
                            ${run.status}
                        </span>
                        ${run.error_message ? `<div class="text-xs text-red-500 mt-1 max-w-xs truncate" title="${run.error_message}">${run.error_message}</div>` : ''}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <div>Discovered: ${run.pages_discovered}</div>
                        <div>Crawled: ${run.pages_crawled}</div>
                        ${run.errors_count > 0 ? `<div class="text-red-500">Errors: ${run.errors_count}</div>` : ''}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        ${new Date(run.created_at).toLocaleString()}
                        <div class="text-xs text-gray-400">Duration: ${run.finished_at ? Math.round((new Date(run.finished_at) - new Date(run.started_at)) / 1000) + 's' : '-'}</div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', loadRuns);
</script>
@endpush
