<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Log Detail — {{ $project->name }}
            </h2>
            <a href="{{ route('logs.index', ['project_id' => $project->id]) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Logs</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Log Detail Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Level</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    @switch($log->level)
                                        @case('debug') bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200 @break
                                        @case('info') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                                        @case('warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                                        @case('error') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                                        @case('critical') bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-100 font-bold @break
                                        @default bg-gray-100 text-gray-800 @endswitch
                                ">
                                    {{ ucfirst($log->level) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Channel</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $log->channel ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $log->file ? basename($log->file) . ':' . $log->line : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $log->occurred_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @if($log->batch_id)
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Batch ID</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100 truncate">{{ $log->batch_id }}</dd>
                        </div>
                        @endif
                        @if($log->trace_id)
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trace ID</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100 truncate">{{ $log->trace_id }}</dd>
                        </div>
                        @endif
                    </div>

                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</h3>
                        <pre class="bg-gray-50 dark:bg-gray-900 rounded-md p-4 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-all">{{ $log->message }}</pre>
                    </div>

                    @if($log->context)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Context</h3>
                        <pre class="bg-gray-50 dark:bg-gray-900 rounded-md p-4 text-sm text-gray-900 dark:text-gray-100 overflow-auto max-h-64">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                    @endif

                    <div>
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full File Path</h3>
                        <code class="text-xs bg-gray-50 dark:bg-gray-900 rounded px-2 py-1 text-gray-900 dark:text-gray-100">{{ $log->file ?: 'N/A' }}</code>
                    </div>
                </div>
            </div>

            <!-- Related Logs (same batch) -->
            @if($related->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Related Logs (Same Batch)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Level</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Message</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($related as $r)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 cursor-pointer" onclick="window.location='{{ route('logs.show', $r->id) }}'">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-xs
                                            @switch($r->level)
                                                @case('error') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                                                @case('critical') bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-100 @break
                                                @case('warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @break
                                                @default bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-200 @endswitch
                                        ">{{ ucfirst($r->level) }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 max-w-sm truncate">{{ $r->message }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ $r->occurred_at->format('H:i:s') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>