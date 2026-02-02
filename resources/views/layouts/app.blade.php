<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SEO Control') }}</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 flex w-64 flex-col bg-gray-900 pt-5 text-white">
            <div class="flex shrink-0 items-center px-4 font-bold text-xl">
                SEO Control
            </div>
            
            <nav class="mt-8 flex-1 space-y-1 px-2" aria-label="Sidebar">
                <a href="{{ route('sites.index') }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 {{ request()->routeIs('sites.index') ? 'bg-gray-800' : '' }}">
                    All Sites
                </a>

                @if(request()->route('site'))
                    <div class="mt-6">
                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-400" id="site-name-display">
                            Site {{ request()->route('site') }} <!-- Placeholder updated by JS -->
                        </h3>
                        <div class="mt-2 space-y-1">
                            <a href="{{ route('sites.overview', request()->route('site')) }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 text-gray-300 hover:text-white">
                                Overview
                            </a>
                            <a href="{{ route('sites.pages.index', request()->route('site')) }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 text-gray-300 hover:text-white">
                                Pages
                            </a>
                            <a href="{{ route('sites.audits.index', request()->route('site')) }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 text-gray-300 hover:text-white">
                                Audits
                            </a>
                            <a href="{{ route('sites.schemas.index', request()->route('site')) }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 text-gray-300 hover:text-white">
                                Schemas
                            </a>
                            <a href="{{ route('sites.links.index', request()->route('site')) }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 text-gray-300 hover:text-white">
                                Internal Links
                            </a>
                            <a href="{{ route('sites.crawl.index', request()->route('site')) }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 text-gray-300 hover:text-white">
                                Crawl Monitor
                            </a>
                            <a href="{{ route('sites.tasks.board', request()->route('site')) }}" class="group flex items-center rounded-md px-2 py-2 text-sm font-medium hover:bg-gray-800 text-gray-300 hover:text-white">
                                Tasks
                            </a>
                        </div>
                    </div>
                @endif
            </nav>
            
            <div class="border-t border-gray-800 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-400 hover:text-white">Logout</button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col pl-64">
            <main class="flex-1">
                <div class="py-6">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8">
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- API Client -->
    <script>
        const API_BASE = '/api/v1';
        
        async function api(endpoint, method = 'GET', body = null) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            };
            if (body) options.body = JSON.stringify(body);
            
            const res = await fetch(API_BASE + endpoint, options);
            if (!res.ok) {
                console.error('API Error', res.status);
                throw new Error('API Error');
            }
            // Handle 204 No Content
            if (res.status === 204) return null;
            return await res.json();
        }

        // Global Site Context
        const SITE_ID = "{{ request()->route('site') }}";
        
        // Auto-load Site Name if in context
        if(SITE_ID) {
            api('/sites/' + SITE_ID).then(site => {
                const el = document.getElementById('site-name-display');
                if(el && site.name) el.textContent = site.name;
            }).catch(e => console.log('Site load check failed', e));
        }
    </script>
    @stack('scripts')
</body>
</html>
