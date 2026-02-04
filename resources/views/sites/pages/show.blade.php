@extends('layouts.app')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <nav class="sm:hidden" aria-label="Back">
                <a href="{{ route('sites.pages.index', request()->route('site')) }}" class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                    <svg class="-ml-1 mr-1 h-5 w-5 flex-shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Back
                </a>
            </nav>
            <nav class="hidden sm:flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div class="flex">
                            <a href="{{ route('sites.pages.index', request()->route('site')) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Pages</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                            </svg>
                            <span class="ml-4 text-sm font-medium text-gray-500" id="breadcrumb-page-id">...</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="mt-2 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight" id="page-url">Loading...</h2>
        </div>
        <div class="mt-4 flex flex-shrink-0 md:ml-4 md:mt-0">
           <a href="#" target="_blank" id="external-link" class="ml-3 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Open URL</a>
        </div>
    </div>
</div>

<!-- Tabs -->
<div x-data="{ tab: 'content' }">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="tab = 'content'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'content', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'content' }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">Content Analysis</button>
            <button @click="tab = 'structure'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'structure', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'structure' }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">Structure</button>
            <button @click="tab = 'raw'" :class="{ 'border-indigo-500 text-indigo-600': tab === 'raw', 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': tab !== 'raw' }" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">Raw Attributes</button>
        </nav>
    </div>

    <!-- Content Tab -->
    <div x-show="tab === 'content'" class="pt-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Score Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Page Score</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="content-score">--</dd>
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-900">Issues</h4>
                        <ul role="list" class="divide-y divide-gray-200 mt-2" id="issues-list">
                            <li class="py-2 text-sm text-gray-500 italic">No issues detected.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Meta Card -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Title</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-words" id="meta-title">-</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-words" id="meta-desc">-</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">H1</dt>
                        <dd class="mt-1 text-sm text-gray-900 break-words" id="meta-h1">-</dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Structure Tab -->
    <div x-show="tab === 'structure'" class="pt-6" style="display: none;">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
             <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Depth from Home</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="struct-depth">--</dd>
                </div>
            </div>
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Inbound Links</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="struct-inbound">--</dd>
                </div>
            </div>
             <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-gray-500">Outbound Links</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900" id="struct-outbound">--</dd>
                </div>
            </div>
        </div>
        <div class="mt-6 rounded-md bg-blue-50 p-4" id="orphan-alert" style="display:none;">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1 md:flex md:justify-between">
                    <p class="text-sm text-blue-700">This page is an Orphan (no internal inbound links).</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Raw Tab -->
    <div x-show="tab === 'raw'" class="pt-6" style="display: none;">
        <div class="bg-gray-50 p-4 rounded-lg overflow-auto">
            <pre class="text-xs text-gray-800" id="raw-json">Loading...</pre>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    const PAGE_ID = "{{ request()->route('page') }}";

    async function loadPage() {
        try {
            const page = await api(`/sites/${SITE_ID}/pages/${PAGE_ID}`);
            renderPage(page);
        } catch (e) {
            console.error(e);
            alert('Failed to load page data');
        }
    }

    function renderPage(page) {
        document.getElementById('page-url').textContent = page.path;
        document.getElementById('breadcrumb-page-id').textContent = page.id;
        document.getElementById('external-link').href = page.url;

        // Content
        const analysis = page.analysis || {};
        document.getElementById('content-score').textContent = analysis.score ?? 'N/A';
        
        const issuesList = document.getElementById('issues-list');
        if (analysis.issues && analysis.issues.length > 0) {
            issuesList.innerHTML = analysis.issues.map(i => `
                <li class="py-2 text-sm">
                    <span class="font-medium text-gray-900">${i.type}</span>
                    <span class="text-gray-500 block">${i.message}</span>
                </li>
            `).join('');
        } else {
            issuesList.innerHTML = '<li class="py-2 text-sm text-gray-500 italic">No issues detected.</li>';
        }

        document.getElementById('meta-title').textContent = analysis.title || '-';
        document.getElementById('meta-desc').textContent = analysis.description || '-';
        document.getElementById('meta-h1').textContent = analysis.h1s ? analysis.h1s.join(', ') : '-';

        // Structure
        const struct = page.structure || {};
        document.getElementById('struct-depth').textContent = struct.depth_from_home ?? '-';
        document.getElementById('struct-inbound').textContent = struct.inbound_count ?? 0;
        document.getElementById('struct-outbound').textContent = struct.outbound_count ?? 0;
        
        if (struct.is_orphan) {
            document.getElementById('orphan-alert').style.display = 'block';
        }

        // Raw
        document.getElementById('raw-json').textContent = JSON.stringify(page, null, 2);
    }

    document.addEventListener('DOMContentLoaded', loadPage);
</script>
@endpush
