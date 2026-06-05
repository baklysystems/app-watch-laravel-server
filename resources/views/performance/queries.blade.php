<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Database Queries</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
            <a href="{{ route('performance.requests', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">
                View HTTP Requests &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stat-card">
                    <div class="stat-icon bg-red-50 dark:bg-red-950/50 ring-red-200 dark:ring-red-800/50">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div class="stat-label">Slow Queries</div>
                    <div class="stat-value text-red-600 dark:text-red-400">{{ $slowCount }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-brand-50 dark:bg-brand-950/50 ring-brand-200 dark:ring-brand-800/50">
                        <svg class="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Average Duration</div>
                    <div class="stat-value">{{ round($avgDuration ?? 0, 2) }} <span class="text-sm font-normal text-gray-400">ms</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-gray-50 dark:bg-gray-800 ring-gray-200 dark:ring-gray-700">
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                    </div>
                    <div class="stat-label">Total Queries</div>
                    <div class="stat-value">{{ $queries->total() }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-brand-300 dark:hover:border-brand-700 transition-colors">
                        <input type="checkbox" name="slow_only" value="1" {{ request('slow_only') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Show only slow queries (>100ms)</span>
                    </label>
                    <a href="{{ route('performance.queries', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">Reset</a>
                </form>
            </div>

            <!-- Queries Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>SQL</th>
                                <th class="w-28">Duration</th>
                                <th>Connection</th>
                                <th>Location</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($queries as $q)
                            <tr>
                                <td>
                                    <code class="text-xs text-gray-800 dark:text-gray-200 block max-w-lg break-all whitespace-pre-wrap">{{ $q->sql }}</code>
                                    @if($q->bindings)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Bindings: {{ json_encode($q->bindings) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-sm font-mono font-semibold {{ ($q->duration_ms > 100) ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-300' }}">
                                        {{ round($q->duration_ms, 2) }} ms
                                    </span>
                                    @if($q->is_slow)
                                        <span class="badge-red ml-1 text-[10px]">Slow</span>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-500 dark:text-gray-400">{{ $q->connection_name ?? '—' }}</td>
                                <td>
                                    @if($q->file)
                                        <span class="text-xs font-mono text-gray-400 dark:text-gray-500 file-path">{{ basename($q->file) }}:{{ $q->line }}</span>
                                    @else — @endif
                                </td>
                                <td class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $q->occurred_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state py-12">
                                        <div class="empty-state-icon">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                                        </div>
                                        <p class="empty-state-title">No queries found</p>
                                        <p class="empty-state-desc">Database queries will appear here as they are captured.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-50 dark:border-gray-800">
                    {{ $queries->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>