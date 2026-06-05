<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Exceptions</h2>
                @if(isset($project))
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="metric-grid mb-6">
                <div class="stat-card">
                    <div class="stat-icon bg-gray-50 dark:bg-gray-800 ring-gray-200 dark:ring-gray-700">
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="stat-label">Total</div>
                    <div class="stat-value">{{ $totalExceptions ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-red-50 dark:bg-red-950/50 ring-red-200 dark:ring-red-800/50">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div class="stat-label">Unresolved</div>
                    <div class="stat-value text-red-600 dark:text-red-400">{{ $unresolvedCount ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-amber-50 dark:bg-amber-950/50 ring-amber-200 dark:ring-amber-800/50">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="stat-label">Critical</div>
                    <div class="stat-value text-amber-600 dark:text-amber-400">{{ $criticalCount ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-blue-50 dark:bg-blue-950/50 ring-blue-200 dark:ring-blue-800/50">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">New Today</div>
                    <div class="stat-value text-blue-600 dark:text-blue-400">{{ $newTodayCount ?? 0 }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4 mb-6">
                <form method="GET" action="{{ route('exceptions.index') }}" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Class, message, file..." class="input-premium !w-64 !py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" class="select-premium !py-2">
                            <option value="">All</option>
                            <option value="unresolved" {{ request('status') == 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="ignored" {{ request('status') == 'ignored' ? 'selected' : '' }}>Ignored</option>
                            <option value="muted" {{ request('status') == 'muted' ? 'selected' : '' }}>Muted</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Severity</label>
                        <select name="severity" class="select-premium !py-2">
                            <option value="">All</option>
                            <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                            <option value="error" {{ request('severity') == 'error' ? 'selected' : '' }}>Error</option>
                            <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                            <option value="debug" {{ request('severity') == 'debug' ? 'selected' : '' }}>Debug</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary btn-sm">Filter</button>
                        <a href="{{ route('exceptions.index', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Exceptions Table -->
            <div class="card overflow-hidden">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Exception</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th class="text-center">
                                <a href="{{ route('exceptions.index', array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => 'occurrence_count', 'direction' => request('sort') == 'occurrence_count' && request('direction') == 'desc' ? 'asc' : 'desc'])) }}" class="sort-link group inline-flex items-center gap-1">
                                    Count
                                    @if(request('sort', 'occurrence_count') == 'occurrence_count')
                                        <svg class="w-3.5 h-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            @if(request('direction', 'desc') == 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 15l4-4 4 4"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4 4 4-4"/>
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th>Last Seen</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exceptions as $exception)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('exceptions.show', $exception->id) }}'">
                                <td>
                                    <div class="max-w-md">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $exception->class }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $exception->message }}</div>
                                        <div class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-1 file-path">{{ basename($exception->file) }}:{{ $exception->line }}</div>
                                    </div>
                                </td>
                                <td>
                                    @switch($exception->severity)
                                        @case('critical') <span class="badge-red">Critical</span> @break
                                        @case('error') <span class="badge-red">Error</span> @break
                                        @case('warning') <span class="badge-amber">Warning</span> @break
                                        @case('info') <span class="badge-blue">Info</span> @break
                                        @default <span class="badge-gray">{{ $exception->severity }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @switch($exception->status)
                                        @case('unresolved') <span class="badge-red">Unresolved</span> @break
                                        @case('resolved') <span class="badge-green">Resolved</span> @break
                                        @case('ignored') <span class="badge-gray">Ignored</span> @break
                                        @case('muted') <span class="badge-blue">Muted</span> @break
                                        @default <span class="badge-gray">{{ $exception->status }}</span>
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $exception->occurrence_count }}</span>
                                </td>
                                <td class="text-sm text-gray-500 dark:text-gray-400">{{ $exception->last_seen_at->diffForHumans() }}</td>
                                <td class="text-right">
                                    <a href="{{ route('exceptions.show', $exception->id) }}" class="text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 text-sm font-semibold transition-colors">View &rarr;</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state py-12">
                                        <div class="empty-state-icon">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        </div>
                                        <p class="empty-state-title">No exceptions found</p>
                                        <p class="empty-state-desc">Deploy the <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">baklysystems/appswatch</code> package to a Laravel app to start capturing exceptions.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $exceptions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>