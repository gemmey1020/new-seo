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
                            <!-- Overview -->
                            <a href="{{ route('sites.overview', request()->route('site')) }}" 
                               class="group flex items-center rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('sites.overview') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <svg class="mr-3 h-6 w-6 flex-shrink-0 {{ request()->routeIs('sites.overview') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                Overview
                            </a>

                            <!-- Pages -->
                            <a href="{{ route('sites.pages.index', request()->route('site')) }}" 
                               class="group flex items-center rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('sites.pages.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <svg class="mr-3 h-6 w-6 flex-shrink-0 {{ request()->routeIs('sites.pages.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                Pages
                            </a>

                            <!-- Structure (Renamed from Internal Links) -->
                            <a href="{{ route('sites.links.index', request()->route('site')) }}" 
                               class="group flex items-center rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('sites.links.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <svg class="mr-3 h-6 w-6 flex-shrink-0 {{ request()->routeIs('sites.links.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                                </svg>
                                Structure
                            </a>

                            <!-- Crawl Monitor -->
                            <a href="{{ route('sites.crawl.index', request()->route('site')) }}" 
                               class="group flex items-center rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('sites.crawl.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <svg class="mr-3 h-6 w-6 flex-shrink-0 {{ request()->routeIs('sites.crawl.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                </svg>
                                Crawl Monitor
                            </a>

                            <!-- Audits -->
                            <a href="{{ route('sites.audits.index', request()->route('site')) }}" 
                               class="group flex items-center rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('sites.audits.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <svg class="mr-3 h-6 w-6 flex-shrink-0 {{ request()->routeIs('sites.audits.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                </svg>
                                Audits
                            </a>

                            <!-- Schemas (Placeholder Icon) -->
                            <a href="{{ route('sites.schemas.index', request()->route('site')) }}" 
                               class="group flex items-center rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('sites.schemas.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <svg class="mr-3 h-6 w-6 flex-shrink-0 {{ request()->routeIs('sites.schemas.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                                </svg>
                                Schemas
                            </a>
                            
                            <div class="my-2 border-t border-gray-800"></div>
                            
                            <!-- Settings -->
                             <a href="{{ route('sites.settings.index', request()->route('site')) }}" 
                               class="group flex items-center rounded-md px-2 py-2 text-sm font-medium {{ request()->routeIs('sites.settings.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                <svg class="mr-3 h-6 w-6 flex-shrink-0 {{ request()->routeIs('sites.settings.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-300' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
                                </svg>
                                Settings
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
