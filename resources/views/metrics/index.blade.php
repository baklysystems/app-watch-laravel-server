<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Metrics</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Custom Application Metrics -->
            <div>
                <h3 class="section-title mb-4">Custom Application Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($customMetrics as $name => $metrics)
                    @php $latest = $metrics->first(); @endphp
                    <div class="card p-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">{{ $name }}</h4>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                            {{ $latest->value }} <span class="text-sm font-normal text-gray-400">{{ $latest->unit }}</span>
                        </div>
                        @if($latest->tags)
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach((array)$latest->tags as $k => $v)
                                <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-mono">{{ $k }}: {{ $v }}</span>
                            @endforeach
                        </div>
                        @endif
                        <div class="mt-3 text-xs text-gray-400">{{ $metrics->count() }} data points, latest: {{ $latest->recorded_at->diffForHumans() }}</div>
                    </div>
                    @empty
                    <div class="col-span-2 card p-8">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <p class="empty-state-title">No custom metrics yet</p>
                            <p class="empty-state-desc">Use the package API to send custom metrics from your applications.</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Integration Metrics -->
            <div>
                <h3 class="section-title mb-4">Integration Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($integrationMetrics as $integration => $metrics)
                    @php $latest = $metrics->first(); @endphp
                    <div class="card p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-950/50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ str_replace('_', ' ', ucfirst($integration)) }}
                            </h4>
                        </div>
                        <div class="space-y-3">
                            @foreach($metrics->groupBy('metric_name')->take(4) as $mname => $group)
                            @php $m = $group->first(); @endphp
                            <div class="flex justify-between items-baseline">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $mname) }}</span>
                                <span class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100">
                                    {{ $m->metric_value }}
                                    @if($m->unit) <span class="text-xs text-gray-400 font-normal">{{ $m->unit }}</span> @endif
                                </span>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-4 text-xs text-gray-400 border-t border-gray-50 dark:border-gray-800 pt-3">Updated {{ $latest->recorded_at->diffForHumans() }}</div>
                    </div>
                    @empty
                    <div class="col-span-2 card p-8">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <p class="empty-state-title">No integration metrics yet</p>
                            <p class="empty-state-desc">Enable integrations in Settings to pull data from external services.</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>