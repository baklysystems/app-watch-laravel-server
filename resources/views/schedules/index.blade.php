<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Scheduled Tasks</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="card p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" class="select-premium !py-2">
                            <option value="">All</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>Running</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary btn-sm">Filter</button>
                        <a href="{{ route('schedules.index', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Tasks Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Command</th>
                                <th>Duration</th>
                                <th>Exit Code</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $task)
                            <tr>
                                <td>
                                    @switch($task->status)
                                        @case('completed') <span class="badge-green">Completed</span> @break
                                        @case('failed') <span class="badge-red">Failed</span> @break
                                        @case('running') <span class="badge-blue">Running</span> @break
                                        @default <span class="badge-gray">{{ $task->status }}</span>
                                    @endswitch
                                </td>
                                <td><span class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $task->command }}</span></td>
                                <td class="text-sm text-gray-500 dark:text-gray-400">{{ $task->duration_ms ? $task->duration_ms . ' ms' : '—' }}</td>
                                <td class="text-sm text-gray-500 dark:text-gray-400">{{ $task->exit_code ?? '—' }}</td>
                                <td class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $task->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state py-12">
                                        <div class="empty-state-icon">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <p class="empty-state-title">No scheduled tasks found</p>
                                        <p class="empty-state-desc">Scheduled task runs will appear here as they execute.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-50 dark:border-gray-800">
                    {{ $tasks->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>