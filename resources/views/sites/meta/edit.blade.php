@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('sites.pages.index', request()->route('site')) }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Pages</a>
    <h1 class="mt-2 text-2xl font-bold text-gray-900">Manage SEO Meta</h1>
    <p class="text-sm text-gray-500" id="page-path-display">Loading page...</p>
</div>

<div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
    <!-- Edit Form -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <form id="meta-form" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900">Title Tag</label>
                        <div class="mt-2">
                            <input type="text" name="title" id="meta-title" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900">Meta Description</label>
                        <div class="mt-2">
                            <textarea name="description" id="meta-description" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900">Robots</label>
                        <div class="mt-2">
                            <input type="text" name="robots" id="meta-robots" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                     <div>
                        <label class="block text-sm font-medium leading-6 text-gray-900">Change Note (Required for History)</label>
                        <div class="mt-2">
                            <input type="text" name="change_note" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" required placeholder="e.g. Updated keyword targeting">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Version History -->
    <div class="space-y-6">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Version History</h3>
                <div class="mt-6 flow-root">
                    <ul id="versions-list" role="list" class="-my-5 divide-y divide-gray-200">
                        <li class="py-4 text-sm text-gray-500">Loading versions...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const PAGE_ID = "{{ request()->route('page') }}";

    async function loadData() {
        // Load Page Info
        const page = await api(`/sites/${SITE_ID}/pages/${PAGE_ID}`);
        document.getElementById('page-path-display').textContent = page.path;

        // Load Meta
        const meta = await api(`/sites/${SITE_ID}/pages/${PAGE_ID}/meta`);
        if(meta) {
            document.getElementById('meta-title').value = meta.title || '';
            document.getElementById('meta-description').value = meta.description || '';
            document.getElementById('meta-robots').value = meta.robots || '';
        }

        // Load Versions
        const versions = await api(`/sites/${SITE_ID}/pages/${PAGE_ID}/meta/versions`);
        const vList = document.getElementById('versions-list');
        if(versions.length) {
            vList.innerHTML = versions.map(v => `
                <li class="py-4">
                    <div class="flex items-center space-x-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900">${v.change_note || 'No note'}</p>
                            <p class="text-xs text-gray-500">${new Date(v.created_at).toLocaleString()}</p>
                        </div>
                    </div>
                </li>
            `).join('');
        } else {
            vList.innerHTML = '<li class="py-4 text-sm text-gray-500">No history yet.</li>';
        }
    }

    document.getElementById('meta-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await api(`/sites/${SITE_ID}/pages/${PAGE_ID}/meta`, 'PUT', Object.fromEntries(fd));
            alert('Saved successfully.');
            loadData(); // Refresh versions
        } catch(err) {
            alert('Failed to save.');
        }
    });

    loadData();
</script>
@endpush
@endsection
