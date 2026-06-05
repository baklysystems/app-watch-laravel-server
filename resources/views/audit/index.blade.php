<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">
                    Audit Log
                    <span class="ml-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">{{ $project->name }}</span>
                </h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">All write operations are logged and immutable</p>
            </div>
            <a href="{{ route('audit.index', ['project_id' => $project->id, 'export' => 1] + request()->all()) }}" class="btn-secondary text-sm">
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <form method="GET" class="card p-4 mb-6 flex flex-wrap gap-4 items-end">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="input-premium !w-auto">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="input-premium !w-auto">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Action</label>
                    <input type="text" name="action" value="{{ request('action') }}" placeholder="Search actions..." class="input-premium !w-auto">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Entity Type</label>
                    <select name="entity_type" class="select-premium !w-auto">
                        <option value="">All</option>
                        @foreach($entityTypes as $type)
                        <option value="{{ $type }}" {{ request('entity_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Entity ID</label>
                    <input type="text" name="entity_id" value="{{ request('entity_id') }}" placeholder="UUID..." class="input-premium !w-auto">
                </div>
                <button type="submit" class="btn-primary !py-2">Filter</button>
            </form>

            <!-- Table -->
            <div class="card overflow-hidden">
                <table class="table-premium w-full">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity Type</th>
                            <th>Entity ID</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                        <tr>
                            <td class="text-xs">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->user->name ?? 'System' }}</td>
                            <td>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                    @if(str_contains($log->action, 'deleted')) bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300
                                    @elseif(str_contains($log->action, 'created')) bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300
                                    @elseif(str_contains($log->action, 'updated') || str_contains($log->action, 'resolved')) bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300
                                    @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @endif">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="text-sm">{{ $log->entity_type }}</td>
                            <td class="text-xs font-mono">{{ \Illuminate\Support\Str::limit($log->entity_id, 12, '') }}</td>
                            <td class="text-xs text-gray-400">{{ $log->ip_address }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 dark:text-gray-500 py-8">No audit log entries found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $auditLogs->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-app-layout>