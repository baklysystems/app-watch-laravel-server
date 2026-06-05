<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $exception->class }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $project->name }} · {{ $exception->last_seen_at->diffForHumans() }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('exceptions.index', ['project_id' => $project->id]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-300">
                    ← Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Status Management -->
            <div class="card p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</span>

                    <form method="POST" action="{{ route('exceptions.update-status', $exception->id) }}" class="flex gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md shadow-sm text-sm px-3 py-2"
                                onchange="this.form.submit()">
                            <option value="unresolved" {{ $exception->status == 'unresolved' ? 'selected' : '' }}>Unresolved</option>
                            <option value="resolved" {{ $exception->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="ignored" {{ $exception->status == 'ignored' ? 'selected' : '' }}>Ignored</option>
                            <option value="muted" {{ $exception->status == 'muted' ? 'selected' : '' }}>Muted</option>
                        </select>
                    </form>

                    <div class="flex gap-4 ml-auto text-sm text-gray-500">
                        <div>
                            <span class="font-medium text-gray-700">Severity:</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                @if($exception->severity == 'critical') bg-red-100 text-red-800
                                @elseif($exception->severity == 'error') bg-orange-100 text-orange-800
                                @elseif($exception->severity == 'warning') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $exception->severity }}
                            </span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Occurrences:</span> {{ $exception->occurrence_count }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">First seen:</span> {{ $exception->first_seen_at?->format('Y-m-d H:i') }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Release:</span> {{ $exception->release ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Error Message -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Message</h3>
                        <div class="bg-red-50 border border-red-200 rounded-md p-4 text-red-800 font-mono text-sm">
                            {{ $exception->message ?: '(no message)' }}
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            {{ $exception->file }}:{{ $exception->line }}
                        </div>
                    </div>

                    <!-- Stack Trace -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Stack Trace</h3>
                        @if($exception->stack_trace)
                            <div class="bg-gray-50 rounded-md p-4 overflow-x-auto text-xs font-mono">
                                @foreach($exception->stack_trace as $index => $frame)
                                    <div class="py-1 {{ $index === 0 ? 'text-red-700 font-semibold' : 'text-gray-700' }}">
                                        #{{ $index }}
                                        @if(isset($frame['file']))
                                            <span class="text-gray-500">{{ $frame['file'] }}</span>
                                            @if(isset($frame['line']))
                                                <span class="text-blue-600">:{{ $frame['line'] }}</span>
                                            @endif
                                        @endif
                                        <span class="text-purple-700">
                                            @if(isset($frame['class']))
                                                {{ $frame['class'] }}{{ $frame['type'] ?? '::' }}
                                            @endif
                                            {{ $frame['function'] ?? '' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-sm">No stack trace available.</p>
                        @endif
                    </div>

                    <!-- Code Snippet -->
                    @if($exception->code_snippet)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Code Snippet</h3>
                        <div class="bg-gray-50 rounded-md p-4 overflow-x-auto text-sm font-mono">
                            @foreach($exception->code_snippet as $lineNo => $code)
                                <div class="flex gap-4 {{ $lineNo == $exception->line ? 'bg-red-100 border-l-4 border-red-500 pl-2' : '' }}">
                                    <span class="text-gray-400 w-8 text-right select-none">{{ $lineNo }}</span>
                                    <span class="{{ $lineNo == $exception->line ? 'text-red-800 font-semibold' : 'text-gray-700' }}">
                                        {{ $code ?: '(empty)' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Request Data -->
                    @if($exception->request_data)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Request Data</h3>
                        <div class="bg-gray-50 rounded-md p-4">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div><span class="font-medium text-gray-700">URL:</span> {{ $exception->request_data['url'] ?? 'N/A' }}</div>
                                <div><span class="font-medium text-gray-700">Method:</span> {{ $exception->request_data['method'] ?? 'N/A' }}</div>
                                <div><span class="font-medium text-gray-700">IP:</span> {{ $exception->request_data['ip'] ?? 'N/A' }}</div>
                                <div><span class="font-medium text-gray-700">User Agent:</span> {{ $exception->request_data['user_agent'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                     @endif

                     <!-- Breadcrumbs Timeline -->
                     @php $breadcrumbs = $exception->breadcrumbs ? json_decode($exception->breadcrumbs, true) : []; @endphp
                     <div class="bg-white shadow-sm sm:rounded-lg p-6" id="breadcrumbs-section">
                         <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Breadcrumbs Timeline</h3>
                         @if(!empty($breadcrumbs))
                             <div class="relative">
                                 <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                                 <div class="space-y-4">
                                     @foreach($breadcrumbs as $breadcrumb)
                                         <div class="relative pl-10">
                                             <div class="absolute left-2.5 top-1.5 w-3 h-3 rounded-full border-2
                                                 @if(($breadcrumb['type'] ?? '') === 'log' && ($breadcrumb['level'] ?? '') === 'error') bg-red-500 border-red-300
                                                 @elseif(($breadcrumb['type'] ?? '') === 'query') bg-blue-500 border-blue-300
                                                 @elseif(($breadcrumb['type'] ?? '') === 'request') bg-green-500 border-green-300
                                                 @else bg-gray-400 border-gray-200 @endif">
                                             </div>
                                             <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                                 <div class="flex items-center justify-between mb-1">
                                                     <span class="text-xs font-mono px-2 py-0.5 rounded-full
                                                         @if(($breadcrumb['type'] ?? '') === 'log') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                         @elseif(($breadcrumb['type'] ?? '') === 'query') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                         @elseif(($breadcrumb['type'] ?? '') === 'request') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                         @else bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 @endif">
                                                         {{ strtoupper($breadcrumb['type'] ?? 'info') }}
                                                     </span>
                                                     <span class="text-xs text-gray-400">{{ $breadcrumb['timestamp'] ?? '' }}</span>
                                                 </div>
                                                 <p class="text-sm text-gray-700 dark:text-gray-300 font-mono break-all">{{ $breadcrumb['message'] ?? $breadcrumb['sql'] ?? $breadcrumb['url'] ?? '' }}</p>
                                                 @if(isset($breadcrumb['duration_ms']))
                                                     <span class="text-xs text-gray-500 mt-1 inline-block">{{ number_format($breadcrumb['duration_ms'], 1) }}ms</span>
                                                 @endif
                                             </div>
                                         </div>
                                     @endforeach
                                 </div>
                             </div>
                         @else
                             <p class="text-sm text-gray-400 italic">No breadcrumbs captured for this exception.</p>
                         @endif
                     </div>

                </div>

                <!-- Sidebar -->
                <div class="space-y-6">

                    <!-- Occurrence History Chart -->
                    @if(isset($chartLabels) && isset($chartData) && count($chartLabels) > 1)
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-3">📈 Occurrence History (30 days)</h3>
                        <div class="h-24">
                            <canvas id="occurrenceChart"></canvas>
                        </div>
                    </div>
                    @endif

                    <!-- Exception Info Card -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Exception Details</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Class</dt>
                                <dd class="text-gray-900 font-mono text-xs break-all">{{ $exception->class }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Fingerprint</dt>
                                <dd class="text-gray-900 font-mono text-xs">{{ substr($exception->fingerprint, 0, 16) }}...</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Environment</dt>
                                <dd class="text-gray-900">{{ $exception->environment }}</dd>
                            </div>
                            @if($exception->release)
                            <div>
                                <dt class="text-gray-500">Release</dt>
                                <dd class="text-gray-900 font-mono text-xs">{{ $exception->release }}</dd>
                            </div>
                            @endif
                            <div>
                                <dt class="text-gray-500">First Seen</dt>
                                <dd class="text-gray-900">{{ $exception->first_seen_at?->format('M d, Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Last Seen</dt>
                                <dd class="text-gray-900">{{ $exception->last_seen_at?->format('M d, Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- User Data -->
                    @if($exception->user_data)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">User Context</h3>
                        <dl class="space-y-2 text-sm">
                            @foreach($exception->user_data as $key => $value)
                            <div>
                                <dt class="text-gray-500">{{ $key }}</dt>
                                <dd class="text-gray-900">{{ $value ?? 'N/A' }}</dd>
                            </div>
                            @endforeach
                        </dl>
                    </div>
                    @endif

                    <!-- Similar Exceptions -->
                    @if($similar && $similar->isNotEmpty())
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Similar Exceptions</h3>
                        <ul class="space-y-2">
                            @foreach($similar as $sim)
                            <li>
                                <a href="{{ route('exceptions.show', $sim->id) }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                                    {{ $sim->class }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $sim->last_seen_at?->diffForHumans() }} · {{ $sim->occurrence_count }} occurrences</div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                </div>

            </div>
        </div>
    </div>

<!-- Occurrence History Chart Script -->
@if(isset($chartLabels) && isset($chartData) && count($chartLabels) > 1)
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('occurrenceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Occurrences',
                    data: @json($chartData),
                    backgroundColor: 'rgba(239, 68, 68, 0.4)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 1,
                    borderRadius: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 6, font: { size: 9 } } },
                    y: { beginAtZero: true, ticks: { font: { size: 9 }, precision: 0 } }
                }
            }
        });
    }
});
</script>
@endif
</x-app-layout>
