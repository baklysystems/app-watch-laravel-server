<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">HTTP Requests</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
            <a href="{{ route('performance.queries', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">
                View Database Queries &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="stat-card">
                    <div class="stat-icon bg-brand-50 dark:bg-brand-950/50 ring-brand-200 dark:ring-brand-800/50">
                        <svg class="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Avg Duration</div>
                    <div class="stat-value">{{ round($avgDuration ?? 0, 2) }} <span class="text-sm font-normal text-gray-400">ms</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple-50 dark:bg-purple-950/50 ring-purple-200 dark:ring-purple-800/50">
                        <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div class="stat-label">Avg Memory</div>
                    <div class="stat-value">{{ round($avgMemory ?? 0, 2) }} <span class="text-sm font-normal text-gray-400">MB</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-gray-50 dark:bg-gray-800 ring-gray-200 dark:ring-gray-700">
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="stat-label">Total Requests</div>
                    <div class="stat-value">{{ $requests->total() }}</div>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Memory</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $r)
                            <tr>
                                <td>
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold
                                        @switch($r->method)
                                            @case('GET') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 @break
                                            @case('POST') bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 @break
                                            @case('PUT') bg-amber-50 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 @break
                                            @case('PATCH') bg-yellow-50 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300 @break
                                            @case('DELETE') bg-red-50 text-red-700 dark:bg-red-900/50 dark:text-red-300 @break
                                            @default bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300
                                        @endswitch
                                    ">{{ $r->method }}</span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-900 dark:text-gray-100 max-w-sm truncate block" title="{{ $r->url }}">{{ $r->url }}</span>
                                </td>
                                <td>
                                    <span class="text-sm font-mono font-semibold
                                        @if($r->status_code >= 200 && $r->status_code < 300) text-emerald-600 dark:text-emerald-400
                                        @elseif($r->status_code >= 300 && $r->status_code < 400) text-amber-600 dark:text-amber-400
                                        @elseif($r->status_code >= 400 && $r->status_code < 500) text-orange-600 dark:text-orange-400
                                        @else text-red-600 dark:text-red-400
                                        @endif
                                    ">{{ $r->status_code }}</span>
                                </td>
                                <td class="text-sm font-mono text-gray-600 dark:text-gray-300">{{ round($r->duration_ms, 2) }} ms</td>
                                <td class="text-sm text-gray-500 dark:text-gray-400">{{ round($r->memory_usage_mb, 2) }} MB</td>
                                <td class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $r->occurred_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state py-12">
                                        <div class="empty-state-icon">
                                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        </div>
                                        <p class="empty-state-title">No requests found</p>
                                        <p class="empty-state-desc">HTTP requests will appear here as they are captured by the middleware.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-gray-50 dark:border-gray-800">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>