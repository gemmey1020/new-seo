@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-semibold text-gray-900">Internal Links</h1>
<!-- Placeholder for Graph, using Table for MVP -->
<div class="mt-8 flow-root">
    <table class="min-w-full divide-y divide-gray-300">
        <thead>
            <tr>
                <th class="py-3.5 pl-4 px-3 text-left text-sm font-semibold text-gray-900">From Page</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Anchor</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">To Page</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Seen</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200" id="links-table"></tbody>
    </table>
</div>

@push('scripts')
<script>
    async function loadLinks() {
        const res = await api(`/sites/${SITE_ID}/links`);
        document.getElementById('links-table').innerHTML = res.data.map(link => `
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 px-3 text-sm text-gray-500">ID: ${link.from_page_id}</td>
                <td class="px-3 py-4 text-sm text-gray-900">${link.anchor_text || '(img)'}</td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">ID: ${link.to_page_id}</td>
                <td class="px-3 py-4 text-sm text-gray-500">${new Date(link.last_seen_at).toLocaleDateString()}</td>
            </tr>
        `).join('');
    }
    loadLinks();
</script>
@endpush
@endsection
