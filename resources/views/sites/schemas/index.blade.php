@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-semibold text-gray-900">Schema Lab</h1>
<p class="mt-2 text-sm text-gray-600">Schemas are managed at the Page level. Select a page to view schemas.</p>
<div class="mt-6">
    <a href="{{ route('sites.pages.index', request()->route('site')) }}" class="rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Go to Pages List</a>
</div>
@endsection
