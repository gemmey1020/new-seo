@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900">Tasks Board</h1>
    <button onclick="createTask()" class="rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">New Task</button>
</div>

<div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
    @foreach(['todo', 'doing', 'done'] as $status)
    <div class="bg-gray-100 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 uppercase">{{ $status }}</h3>
        <div id="col-{{ $status }}" class="mt-4 space-y-4">
            <!-- JS -->
        </div>
    </div>
    @endforeach
</div>

@push('scripts')
<script>
    async function loadTasks() {
        const res = await api(`/sites/${SITE_ID}/tasks?per_page=100`);
        const tasks = res.data;
        
        ['todo', 'doing', 'done'].forEach(status => {
            const col = document.getElementById(`col-${status}`);
            const items = tasks.filter(t => t.status === status);
            
            col.innerHTML = items.map(t => `
                <div class="bg-white p-4 rounded shadow">
                    <h4 class="font-bold text-sm text-gray-900">${t.title}</h4>
                    <p class="text-xs text-gray-500 mt-1">${t.priority}</p>
                    <div class="mt-3 flex justify-end gap-2">
                        ${status !== 'done' ? `<button onclick="moveTask(${t.id}, '${nextStatus(status)}')" class="text-xs text-blue-600">Next &rarr;</button>` : ''}
                    </div>
                </div>
            `).join('');
        });
    }

    function nextStatus(s) {
        if(s === 'todo') return 'doing';
        if(s === 'doing') return 'done';
        return 'done';
    }

    async function moveTask(id, status) {
        await api(`/sites/${SITE_ID}/tasks/${id}`, 'PUT', { status });
        loadTasks();
    }

    async function createTask() {
        const title = prompt('Task Title:');
        if(!title) return;
        await api(`/sites/${SITE_ID}/tasks`, 'POST', {
            title,
            priority: 'medium',
            status: 'todo'
        });
        loadTasks();
    }

    loadTasks();
</script>
@endpush
@endsection
