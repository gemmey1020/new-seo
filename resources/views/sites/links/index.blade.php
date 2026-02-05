@extends('layouts.app')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Internal Link Structure</h1>
        <p class="mt-2 text-sm text-gray-700">Analysis of the internal link graph (Orphans, Depth, Authority).</p>
    </div>
</div>

<div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Orphans Card -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Pages Not Linked Internally</h3>
            <p class="mt-1 text-sm text-gray-500">
                These pages have no internal links pointing to them, making them harder for search engines to discover.
            </p>
            <div class="mt-2 p-3 bg-blue-50 border-l-4 border-blue-400 text-blue-800 text-xs rounded">
                <strong>Why this matters:</strong> Search engines crawl by following links. Pages without internal links may not be indexed.
            </div>
            <div class="mt-4">
                 <div class="flow-root">
                    <ul role="list" class="-my-5 divide-y divide-gray-200" id="orphans-list">
                        <li class="py-5 text-center text-sm text-gray-500">Loading...</li>
                    </ul>
                </div>
                <div class="mt-6">
                    <a href="{{ route('sites.pages.index', request()->route('site')) }}?orphan=1" class="flex w-full items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">View all unlinked pages</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Depth Distribution Card -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900">Page Accessibility</h3>
            <p class="mt-1 text-sm text-gray-500">
                How many clicks it takes to reach pages from your homepage.
            </p>
            <p class="mt-1 text-xs text-gray-400">
                💡 Pages deeper than 3 clicks may be harder for search engines to discover.
            </p>
            <div class="mt-4 relative h-48 flex items-end justify-center gap-4 border-b border-gray-200" id="depth-chart">
                 <!-- Placeholder for Chart logic -->
                 <p class="text-sm text-gray-400 self-center">Chart loading...</p>
            </div>
            <div class="mt-2 flex justify-between text-xs text-gray-500">
                <span>Home (0)</span>
                <span>1</span>
                <span>2</span>
                <span>3+</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadStructure() {
        // Fetch top 5 orphans
        try {
            const res = await api(`/sites/${SITE_ID}/pages?orphan=1&per_page=5`);
            renderOrphans(res.data);
            
            // Mock depth chart for now as we don't have an aggregation endpoint yet
            // In a real implementation we'd add an endpoint for depth stats
            renderDepthPlaceholder(); 
        } catch (e) {
            console.error(e);
        }
    }

    function renderOrphans(pages) {
        const list = document.getElementById('orphans-list');
        if (pages.length === 0) {
            list.innerHTML = '<li class="py-5 text-center text-sm text-gray-500">Great! All pages are linked internally.</li>';
            return;
        }

        list.innerHTML = pages.map(page => `
            <li class="py-4">
                <div class="flex items-center space-x-4">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">${page.path}</p>
                        <p class="truncate text-xs text-gray-500">${page.url}</p>
                    </div>
                    <div>
                        <a href="/sites/${SITE_ID}/pages/${page.id}" class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">View</a>
                    </div>
                </div>
            </li>
        `).join('');
    }

    function renderDepthPlaceholder() {
        const chart = document.getElementById('depth-chart');
        chart.innerHTML = `
            <div class="w-12 bg-indigo-200 rounded-t" style="height: 10%"></div>
            <div class="w-12 bg-indigo-300 rounded-t" style="height: 40%"></div>
            <div class="w-12 bg-indigo-500 rounded-t" style="height: 60%"></div>
            <div class="w-12 bg-indigo-700 rounded-t" style="height: 20%"></div>
        `;
    }

    document.addEventListener('DOMContentLoaded', loadStructure);
</script>
@endpush
