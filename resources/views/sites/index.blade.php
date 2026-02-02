@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-semibold text-gray-900">Your Sites</h1>

<div class="mt-6" id="sites-container">
    <p class="text-gray-500">Loading sites...</p>
</div>

<!-- Simple Create Form (Admin) -->
<div class="mt-8 border-t pt-6">
    <h3 class="text-lg font-medium">Add New Site</h3>
    <form id="create-site-form" class="mt-4 flex gap-4">
        <input type="text" name="name" placeholder="Site Name" class="rounded border p-2" required>
        <input type="text" name="domain" placeholder="Domain (example.com)" class="rounded border p-2" required>
        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Add Site</button>
    </form>
</div>

@push('scripts')
<script>
    async function loadSites() {
        try {
            const sites = await api('/sites');
            const container = document.getElementById('sites-container');
            
            if (sites.length === 0) {
                container.innerHTML = '<p>No sites found.</p>';
                return;
            }

            let html = '<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">';
            sites.forEach(site => {
                html += `
                    <a href="/sites/${site.id}" class="block rounded-lg bg-white p-6 shadow hover:shadow-md transition">
                        <h3 class="text-xl font-bold text-gray-900">${site.name}</h3>
                        <p class="text-sm text-gray-500">${site.domain}</p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                Active
                            </span>
                            <span class="text-xs text-gray-400">ID: ${site.id}</span>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        } catch (e) {
            document.getElementById('sites-container').innerHTML = '<p class="text-red-500">Failed to load sites.</p>';
        }
    }

    document.getElementById('create-site-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            await api('/sites', 'POST', Object.fromEntries(fd));
            e.target.reset();
            loadSites();
        } catch (err) {
            alert('Failed to create site. Ensure you have admin permissions.');
        }
    });

    loadSites();
</script>
@endpush
@endsection
