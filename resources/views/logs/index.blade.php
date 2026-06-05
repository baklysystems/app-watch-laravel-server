<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Logs</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="card p-4 mb-6">
                <form method="GET" action="{{ route('logs.index') }}" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search logs..." class="input-premium !w-56 !py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Level</label>
                        <select name="level" class="select-premium !py-2">
                            <option value="">All</option>
                            @foreach(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level)
                                <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary btn-sm">Filter</button>
                        <a href="{{ route('logs.index', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Logs Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Message</th>
                                <th>Location</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('logs.show', $log->id) }}'">
                                <td>
                                    <span class="badge {{ $log->level === 'error' || $log->level === 'critical' || $log->level === 'alert' || $log->level === 'emergency' ? 'badge-red' : ($log->level === 'warning' ? 'badge-amber' : ($log->level === 'info' ? 'badge-blue' : 'badge-gray')) }}">{{ ucfirst($log->level) }}</span>
                                </td>
                                <td>
                                    <div class="max-w-xl">
                                        <div class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $log->message }}</div>
                                        @if($log->context)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 truncate font-mono">{{ json_encode($log->context) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ $log->file ? basename($log->file).':'.$log->line : '—' }}</td>
                                <td class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $log->occurred_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state py-12">
                                        <div class="empty-state-icon">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <p class="empty-state-title">No logs found</p>
                                        <p class="empty-state-desc">Logs will appear here as your applications send data.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-50 dark:border-gray-800">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>