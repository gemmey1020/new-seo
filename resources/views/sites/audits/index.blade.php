@extends('layouts.app')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Audit Center</h1>
        <p class="mt-2 text-sm text-gray-700">SEO issues detected on your site. Run audits to discover new issues.</p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <button id="run-audit-btn" onclick="runAudit()" class="rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Run New Audit</button>
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
    // D.2 - Toast notification for audit actions
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

    async function loadAudits(url = `/sites/${SITE_ID}/audits`) {
        let endpoint = url.includes('/api/v1') ? url.split('/api/v1')[1] : url;
        const res = await api(endpoint);
        const tbody = document.getElementById('audits-table');
        
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-gray-500">No audit issues found. Click "Run New Audit" to scan for SEO issues.</td></tr>`;
            return;
        }
        
        tbody.innerHTML = res.data.map(audit => `
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 px-3 text-sm">
                    <span class="inline-flex rounded-full px-2 text-xs font-semibold uppercase ${
                        audit.severity === 'critical' ? 'bg-red-100 text-red-800' : 
                        audit.severity === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                        'bg-blue-100 text-blue-800'
                    }">
                        ${audit.severity}
                    </span>
                </td>
                <td class="px-3 py-4 text-sm text-gray-500">${audit.description || 'No description'}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">${audit.status}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${new Date(audit.detected_at).toLocaleDateString()}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    ${audit.status === 'open' ? `<button onclick="markFixed(${audit.id})" class="text-indigo-600 hover:text-indigo-900">Mark Fixed</button>` : '<span class="text-green-600">✓ Fixed</span>'}
                </td>
            </tr>
        `).join('');
    }

    // D.2 - Run Audit with feedback
    async function runAudit() {
        if(!confirm('Run full audit? This will scan all pages for SEO issues.')) return;
        
        const btn = document.getElementById('run-audit-btn');
        const originalText = btn.textContent;
        btn.textContent = 'Running...';
        btn.disabled = true;
        
        try {
            await api(`/sites/${SITE_ID}/audits/run`, 'POST');
            showToast('Audit started! Results will appear below.', 'success');
            // Reload after delay to show new results
            setTimeout(() => loadAudits(), 2000);
        } catch(e) {
            showToast(e.message || 'Failed to run audit.', 'error');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }

    // D.2 - Mark Fixed with feedback
    async function markFixed(id) {
        if(!confirm('Mark this issue as fixed?')) return;
        
        try {
            await api(`/sites/${SITE_ID}/audits/${id}`, 'PUT', { status: 'fixed' });
            showToast('Issue marked as fixed!', 'success');
            loadAudits();
        } catch(e) {
            showToast(e.message || 'Failed to update.', 'error');
        }
    }

    loadAudits();
</script>
@endpush
@endsection
