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

    async function importSitemap() {
        if(!confirm('Import/Sync sitemaps now?')) return;
        try {
            await api(`/sites/${SITE_ID}/pages/import-sitemap`, 'POST');
            alert('Import queued.');
        } catch(e) { alert('Error triggering import.'); }
    }

    async function createPage() {
        const path = prompt('Enter page path (e.g. /about):');
        if(!path) return;
        try {
            await api(`/sites/${SITE_ID}/pages`, 'POST', {
                url: 'http://placeholder' + path, // MVP hack, should ask for full URL or auto-prefix 
                path: path,
                site_id: SITE_ID // Controller expects site_id
            });
            loadPages();
        } catch(e) { alert('Failed to create page.'); }
    }

    loadPages();
</script>
@endpush
@endsection
