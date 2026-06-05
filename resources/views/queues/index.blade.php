<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Queue Jobs</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="metric-grid mb-6">
                <div class="stat-card">
                    <div class="stat-icon bg-amber-50 dark:bg-amber-950/50 ring-amber-200 dark:ring-amber-800/50">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Pending</div>
                    <div class="stat-value text-amber-600 dark:text-amber-400">{{ $pending }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-blue-50 dark:bg-blue-950/50 ring-blue-200 dark:ring-blue-800/50">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="stat-label">Processing</div>
                    <div class="stat-value text-blue-600 dark:text-blue-400">{{ $processing }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-emerald-50 dark:bg-emerald-950/50 ring-emerald-200 dark:ring-emerald-800/50">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Completed</div>
                    <div class="stat-value text-emerald-600 dark:text-emerald-400">{{ $completed }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-red-50 dark:bg-red-950/50 ring-red-200 dark:ring-red-800/50">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div class="stat-label">Failed</div>
                    <div class="stat-value text-red-600 dark:text-red-400">{{ $failed }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" class="select-premium !py-2">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Queue</label>
                        <input type="text" name="queue" value="{{ request('queue') }}" placeholder="Queue name..." class="input-premium !w-40 !py-2">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary btn-sm">Filter</button>
                        <a href="{{ route('queues.index', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Jobs Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Job</th>
                                <th>Queue</th>
                                <th class="text-center">Attempt</th>
                                <th>Duration</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobs as $job)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('queues.show', $job->id) }}'">
                                <td>
                                    @switch($job->status)
                                        @case('pending') <span class="badge-amber">Pending</span> @break
                                        @case('processing') <span class="badge-blue">Processing</span> @break
                                        @case('completed') <span class="badge-green">Completed</span> @break
                                        @case('failed') <span class="badge-red">Failed</span> @break
                                        @default <span class="badge-gray">{{ $job->status }}</span>
                                    @endswitch
                                </td>
                                <td><span class="text-sm font-medium text-gray-900 dark:text-gray-100 max-w-xs truncate block">{{ $job->job_name }}</span></td>
                                <td class="text-sm text-gray-500 dark:text-gray-400">{{ $job->queue ?? 'default' }}</td>
                                <td class="text-center text-sm text-gray-500 dark:text-gray-400">{{ $job->attempt }}/{{ $job->max_attempts }}</td>
                                <td class="text-sm text-gray-500 dark:text-gray-400">{{ $job->duration_ms !== null ? $job->duration_ms . ' ms' : '—' }}</td>
                                <td class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $job->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state py-12">
                                        <div class="empty-state-icon">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        </div>
                                        <p class="empty-state-title">No queue jobs found</p>
                                        <p class="empty-state-desc">Queue jobs will appear here as they are dispatched.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-50 dark:border-gray-800">
                    {{ $jobs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>