@extends('layouts.app')

@section('content')
<div class="md:flex md:items-center md:justify-between">
    <div class="min-w-0 flex-1">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Sitemaps</h2>
        <p class="mt-2 text-sm text-gray-500">Configure and generate XML sitemaps.</p>
    </div>
    <div class="mt-4 flex md:ml-4 md:mt-0">
        <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            Preview XML
        </button>
        <button type="button" class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Generate Now
        </button>
    </div>
</div>

<div class="mt-8 bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-base font-semibold leading-6 text-gray-900">Sitemap Status</h3>
        <div class="mt-2 max-w-xl text-sm text-gray-500">
            <p>Your sitemap has not been generated yet.</p>
        </div>
    </div>
</div>
@endsection
