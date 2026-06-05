<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Job Detail — {{ $project->name }}
            </h2>
            <a href="{{ route('queues.index', ['project_id' => $project->id]) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">&larr; Back to Jobs</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</dt>
                            <dd class="mt-1">
                                @switch($job->status)
                                    @case('pending')<span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pending</span>@break
                                    @case('processing')<span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Processing</span>@break
                                    @case('completed')<span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Completed</span>@break
                                    @case('failed')<span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Failed</span>@break
                                    @default<span class="px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-600">{{ $job->status }}</span>
                                @endswitch
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Job Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $job->job_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Queue / Connection</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $job->connection ?? '—' }} / {{ $job->queue ?? 'default' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Attempt</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $job->attempt }} of {{ $job->max_attempts }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $job->duration_ms !== null ? $job->duration_ms . ' ms' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Queued At</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $job->queued_at ? $job->queued_at->format('Y-m-d H:i:s') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started At</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $job->started_at ? $job->started_at->format('Y-m-d H:i:s') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Finished At</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $job->finished_at ? $job->finished_at->format('Y-m-d H:i:s') : '—' }}</dd>
                        </div>
                    </div>

                    @if($job->exception)
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-md">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300 mb-2">Linked Exception</h3>
                        <a href="{{ route('exceptions.show', $job->exception->id) }}" class="text-sm text-red-600 dark:text-red-400 hover:underline">
                            {{ $job->exception->class }}: {{ Str::limit($job->exception->message, 120) }}
                        </a>
                    </div>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payload</h3>
                        <pre class="bg-gray-50 dark:bg-gray-900 rounded-md p-4 text-sm text-gray-900 dark:text-gray-100 overflow-auto max-h-96">{{ json_encode($job->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>