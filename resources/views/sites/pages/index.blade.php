@extends('layouts.app')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Pages</h1>
        <p class="mt-2 text-sm text-gray-700">Pages discovered during crawling, evaluated against SEO policies.</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex gap-2">
        <select id="filter-orphan" onchange="loadPages(1)" class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="0">All Pages</option>
            <option value="1">Orphans Only</option>
        </select>
        <select id="filter-policy" onchange="loadPages(1)" class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">All Status</option>
            <option value="FAIL">Needs Attention</option>
            <option value="WARN">Could Be Better</option>
            <option value="PASS">Healthy</option>
        </select>
    </div>
</div>

<div class="mt-8 flex flex-col">
    <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">URL</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Policy Status</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Priority Issues</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Last Crawl</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                <span class="sr-only">View</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="pages-table-body" class="divide-y divide-gray-200 bg-white">
                        <tr>
                            <td colspan="5" class="py-4 text-center text-sm text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 flex items-center justify-between">
    <div class="flex-1 flex justify-between sm:hidden">
        <button onclick="changePage(-1)" id="mobile-prev" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>
        <button onclick="changePage(1)" id="mobile-next" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>
    </div>
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Showing <span class="font-medium" id="page-from">0</span> to <span class="font-medium" id="page-to">0</span> of <span class="font-medium" id="page-total">0</span> results
            </p>
        </div>
        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button onclick="changePage(-1)" id="desk-prev" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                </button>
                <button onclick="changePage(1)" id="desk-next" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                </button>
            </nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let lastPage = 1;

    async function loadPages(page = 1) {
        currentPage = page;
        const orphan = document.getElementById('filter-orphan').value;
        const policyFilter = document.getElementById('filter-policy').value;
        const tbody = document.getElementById('pages-table-body');
        
        tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-sm text-gray-500">Loading...</td></tr>';

        try {
            const res = await api(`/sites/${SITE_ID}/pages?page=${page}&orphan=${orphan}&include_policy=1`);
            renderTable(res.data, policyFilter);
            updatePagination(res);
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-sm text-red-500">Error loading pages.</td></tr>';
        }
    }

    function renderTable(pages, policyFilter) {
        const tbody = document.getElementById('pages-table-body');
        
        // Client-side filter for policy status
        let filteredPages = pages;
        if (policyFilter) {
            filteredPages = pages.filter(p => p.policy_summary?.status === policyFilter);
        }
        
        if (filteredPages.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-sm text-gray-500">No pages found.</td></tr>';
            return;
        }

        tbody.innerHTML = filteredPages.map(page => {
            const policy = page.policy_summary || {};
            const status = policy.status || 'PENDING';
            
            // Count CRITICAL + HIGH violations only (Priority Issues)
            const violations = page.violations_preview || [];
            const priorityIssues = violations.filter(v => 
                v.severity === 'CRITICAL' || v.severity === 'HIGH'
            ).length;
            
            // Status badge colors (non-alarming per Phase 0.5)
            const statusConfig = {
                'FAIL': { bg: 'bg-orange-100', text: 'text-orange-800', label: 'Needs Attention', border: 'border-l-4 border-orange-500' },
                'WARN': { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Could Be Better', border: 'border-l-4 border-yellow-400' },
                'PASS': { bg: 'bg-green-100', text: 'text-green-800', label: 'Healthy', border: '' },
                'PENDING': { bg: 'bg-gray-100', text: 'text-gray-600', label: 'Pending', border: '' }
            };
            
            const config = statusConfig[status] || statusConfig['PENDING'];
            const hasCritical = violations.some(v => v.severity === 'CRITICAL');
            const hasHigh = violations.some(v => v.severity === 'HIGH');
            const borderClass = hasCritical ? 'border-l-4 border-red-500' : (hasHigh ? 'border-l-4 border-orange-500' : '');

            return `
                <tr class="${borderClass}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                        <div class="font-medium text-gray-900 truncate max-w-md" title="${page.url}">${page.path}</div>
                        <div class="text-gray-500 text-xs">${page.url}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.bg} ${config.text}">
                            ${config.label}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        ${priorityIssues > 0 
                            ? `<span class="text-orange-600 font-medium">${priorityIssues} urgent</span>` 
                            : '<span class="text-gray-400">—</span>'}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        ${page.http_status_last ? `<span class="${page.http_status_last === 200 ? 'text-green-600' : 'text-red-600'} font-bold">${page.http_status_last}</span>` : '<span class="text-gray-400">Pending</span>'}
                        <div class="text-xs text-gray-400">${page.last_crawled_at ? new Date(page.last_crawled_at).toLocaleDateString() : 'Never'}</div>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="/sites/${SITE_ID}/pages/${page.id}" class="text-indigo-600 hover:text-indigo-900">View</a>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updatePagination(res) {
        lastPage = res.last_page;
        document.getElementById('page-from').textContent = res.from || 0;
        document.getElementById('page-to').textContent = res.to || 0;
        document.getElementById('page-total').textContent = res.total || 0;

        const prevBtn = document.getElementById('desk-prev');
        const nextBtn = document.getElementById('desk-next');
        
        prevBtn.disabled = res.current_page <= 1;
        nextBtn.disabled = res.current_page >= res.last_page;
    }

    function changePage(delta) {
        const newPage = currentPage + delta;
        if (newPage >= 1 && newPage <= lastPage) {
            loadPages(newPage);
        }
    }

    document.addEventListener('DOMContentLoaded', () => loadPages(1));
</script>
@endpush
