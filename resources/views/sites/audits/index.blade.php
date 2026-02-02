@extends('layouts.app')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Audit Center</h1>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <button onclick="runAudit()" class="rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Run New Audit</button>
    </div>
</div>

<div class="mt-8 flow-root">
    <table class="min-w-full divide-y divide-gray-300">
        <thead>
            <tr>
                <th class="py-3.5 pl-4 px-3 text-left text-sm font-semibold text-gray-900">Severity</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Description</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Detected</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200" id="audits-table"></tbody>
    </table>
    <div class="mt-4" id="pagination"></div>
</div>

@push('scripts')
<script>
    async function loadAudits(url = `/sites/${SITE_ID}/audits`) {
        let endpoint = url.includes('/api/v1') ? url.split('/api/v1')[1] : url;
        const res = await api(endpoint);
        
        document.getElementById('audits-table').innerHTML = res.data.map(audit => `
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 px-3 text-sm">
                    <span class="inline-flex rounded-full bg-${audit.severity === 'critical' ? 'red' : 'yellow'}-100 px-2 text-xs font-semibold text-${audit.severity === 'critical' ? 'red' : 'yellow'}-800 uppercase">
                        ${audit.severity}
                    </span>
                </td>
                <td class="px-3 py-4 text-sm text-gray-500">${audit.description}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">${audit.status}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${new Date(audit.detected_at).toLocaleDateString()}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    ${audit.status === 'open' ? `<button onclick="markFixed(${audit.id})" class="text-indigo-600 hover:text-indigo-900">Mark Fixed</button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    async function runAudit() {
        if(!confirm('Run full audit?')) return;
        await api(`/sites/${SITE_ID}/audits/run`, 'POST');
        alert('Audit queued.');
    }

    async function markFixed(id) {
        if(!confirm('Mark as fixed?')) return;
        await api(`/sites/${SITE_ID}/audits/${id}`, 'PUT', { status: 'fixed' });
        loadAudits();
    }

    loadAudits();
</script>
@endpush
@endsection
