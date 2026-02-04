@extends('layouts.app')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Pages</h1>
        <p class="mt-2 text-sm text-gray-700">A list of all pages discovered on this site.</p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <button onclick="importSitemap()" class="mr-2 rounded bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Sync Sitemap</button>
        <button onclick="createPage()" class="rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Add Page</button>
    </div>
</div>

<div class="mt-8 flow-root">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Path</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Crawled</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-0">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" id="pages-table-body">
                    <!-- JS Populated -->
                </tbody>
            </table>
        </div>
    </div>
    <!-- Pagination -->
    <div class="mt-4" id="pagination-controls"></div>
</div>

@push('scripts')
<script>
    // C.3 - Toast notification system for visual feedback
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

    async function loadPages(pageUrl = `/sites/${SITE_ID}/pages`) {
        // Strip API_BASE if handled by api(), but pagination returns full URL. 
        // Our api helper appends API_BASE. 
        // So we need to handle pagination links carefully.
        // Pagination return: "http://localhost/api/v1/sites/..."
        // api() expects endpoint relative to v1.
        
        let endpoint = pageUrl;
        if (pageUrl.includes('/api/v1')) {
             endpoint = pageUrl.split('/api/v1')[1];
        }

        const res = await api(endpoint);
        const tbody = document.getElementById('pages-table-body');
        
        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-gray-500">No pages found. Use "Add Page" or "Sync Sitemap" to populate.</td></tr>`;
            document.getElementById('pagination-controls').innerHTML = '';
            return;
        }

        tbody.innerHTML = res.data.map(page => `
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                    <a href="/sites/${SITE_ID}/pages/${page.id}" class="text-indigo-600 hover:text-indigo-900">${page.path}</a>
                    <div class="text-xs text-gray-500">${page.page_type || 'General'}</div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <span class="inline-flex rounded-full bg-${page.http_status_last === 200 ? 'green' : 'red'}-100 px-2 text-xs font-semibold leading-5 text-${page.http_status_last === 200 ? 'green' : 'red'}-800">
                        ${page.http_status_last || 'N/A'}
                    </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    ${page.last_crawled_at ? new Date(page.last_crawled_at).toLocaleDateString() : 'Never'}
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <a href="/sites/${SITE_ID}/pages/${page.id}/meta" class="text-indigo-600 hover:text-indigo-900 mr-4">Meta</a>
                    <a href="/sites/${SITE_ID}/pages/${page.id}" class="text-gray-600 hover:text-gray-900">Details</a>
                </td>
            </tr>
        `).join('');

        // Simple Next/Prev
        const pag = document.getElementById('pagination-controls');
        pag.innerHTML = `
            <button ${!res.prev_page_url ? 'disabled' : ''} onclick="loadPages('${res.prev_page_url}')" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
            <span class="px-2 text-sm text-gray-600">Page ${res.current_page} of ${res.last_page}</span>
            <button ${!res.next_page_url ? 'disabled' : ''} onclick="loadPages('${res.next_page_url}')" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
        `;
    }

    // C.1 - Sync Sitemap with visual feedback
    async function importSitemap() {
        if(!confirm('Import/Sync sitemaps now?')) return;
        
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Syncing...';
        btn.disabled = true;
        
        try {
            const result = await api(`/sites/${SITE_ID}/pages/import-sitemap`, 'POST');
            showToast('Sitemap sync initiated! Pages will appear after processing.', 'success');
            // Reload pages after short delay to show new data
            setTimeout(() => loadPages(), 1500);
        } catch(e) {
            const errorMsg = e.message || 'Error triggering import.';
            showToast(errorMsg, 'error');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }

    // C.2 - Add Page with modal-style prompt and feedback
    async function createPage() {
        const path = prompt('Enter page path (e.g. /about):');
        if(!path) return;
        
        // Validate path starts with /
        if (!path.startsWith('/')) {
            showToast('Path must start with /', 'error');
            return;
        }
        
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Adding...';
        btn.disabled = true;
        
        try {
            // Build full URL from site domain
            const siteInfo = await api(`/sites/${SITE_ID}`);
            const fullUrl = `https://${siteInfo.domain}${path}`;
            
            await api(`/sites/${SITE_ID}/pages`, 'POST', {
                url: fullUrl,
                path: path,
                site_id: SITE_ID,
                page_type: 'general',
                index_status: 'unknown',
                depth_level: path.split('/').filter(Boolean).length
            });
            
            showToast(`Page "${path}" created successfully!`, 'success');
            loadPages();
        } catch(e) {
            const errorMsg = e.message || 'Failed to create page.';
            showToast(errorMsg, 'error');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }

    loadPages();
</script>
@endpush
@endsection
