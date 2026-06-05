<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">
                    {{ $title }}
                    @if($project)
                    <span class="ml-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">{{ $project->name }}</span>
                    @endif
                </h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">
                    @if(empty($config['enabled'] ?? false))
                        <span class="text-amber-500 dark:text-amber-400">⚠ Integration is disabled — enable in <a href="{{ route('settings.index', ['project_id' => $project?->id]) }}" class="underline">Settings</a></span>
                    @else
                        Metrics, trends, and activity from your {{ $title }} integration
                    @endif
                </p>
            </div>
            @if($project)
            <a href="{{ route('settings.index', ['project_id' => $project->id]) }}#integrations" class="btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Configure
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(!$project)
            <div class="card p-12 text-center">
                <div class="empty-state-icon mx-auto">
                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mt-4">No project selected</h3>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Select a project from the dropdown above to view integration data.</p>
            </div>
            @elseif(empty($config['enabled'] ?? false))
            <!-- Disabled Integration -->
            <div class="card p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center mb-4 ring-1 ring-amber-200 dark:ring-amber-800/50">
                    <svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Integration Not Enabled</h3>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2 max-w-md mx-auto">Enable {{ $title }} in your project settings to start collecting metrics.</p>
                <a href="{{ route('settings.index', ['project_id' => $project->id]) }}" class="btn-primary mt-6 inline-block">Open Settings</a>
            </div>
            @elseif(empty($metrics))
            <!-- No Data Yet -->
            <div class="card p-12 text-center">
                <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 dark:bg-brand-950/50 flex items-center justify-center mb-4 ring-1 ring-brand-200 dark:ring-brand-800/50">
                    <svg class="w-8 h-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Waiting for Data</h3>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2 max-w-md mx-auto">Metrics are being polled. Data will appear here once the next polling cycle completes.</p>
            </div>
            @else

            <!-- ============ GOOGLE ANALYTICS ============ -->
            @if($integration === 'google_analytics')
            @php
                $gaLatest = $metrics[0] ?? null;
                $gaPageViews = collect($pageViews ?? [])->reverse()->values();
                $gaActiveUsers = collect($activeUsers ?? [])->reverse()->values();
                $gaSessions = collect($metrics)->where('metric_name', 'sessions')->reverse()->values();
                $gaBounceRate = collect($metrics)->where('metric_name', 'bounce_rate')->reverse()->values();
            @endphp

            <!-- Stats Grid -->
            <div class="metric-grid mb-6">
                @foreach(['page_views' => 'Page Views', 'active_users' => 'Active Users', 'sessions' => 'Sessions', 'bounce_rate' => 'Bounce Rate'] as $mKey => $mLabel)
                    @php $val = collect($metrics)->where('metric_name', $mKey)->first(); @endphp
                    <div class="stat-card">
                        <div class="stat-icon bg-orange-50 dark:bg-orange-950/50 ring-orange-200 dark:ring-orange-800/50">
                            <span class="font-bold text-orange-500 text-xs">GA</span>
                        </div>
                        <div class="stat-label">{{ $mLabel }}</div>
                        <div class="stat-value">{{ $val ? number_format($val['metric_value'] ?? 0) : '—' }}</div>
                        <div class="stat-sub">{{ $val ? \Carbon\Carbon::parse($val['recorded_at'])->diffForHumans() : 'no data' }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                @if($gaPageViews->isNotEmpty())
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Page Views Trend</h3>
                    <div class="h-56"><canvas id="gaPageViewsChart"></canvas></div>
                </div>
                @endif
                @if($gaActiveUsers->isNotEmpty())
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Active Users Trend</h3>
                    <div class="h-56"><canvas id="gaUsersChart"></canvas></div>
                </div>
                @endif
            </div>

            <!-- Metrics Table -->
            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Metrics</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800">
                                <th class="px-5 py-3">Metric</th>
                                <th class="px-5 py-3">Value</th>
                                <th class="px-5 py-3">Unit</th>
                                <th class="px-5 py-3">Recorded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($metrics as $m)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ ucwords(str_replace('_', ' ', $m['metric_name'])) }}</td>
                                <td class="px-5 py-3 font-mono text-gray-700 dark:text-gray-300">{{ number_format($m['metric_value'] ?? 0) }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $m['unit'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ \Carbon\Carbon::parse($m['recorded_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($gaPageViews->isNotEmpty() || $gaActiveUsers->isNotEmpty())
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#94a3b8' : '#64748b';
                    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';
                    const defaults = {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { color: textColor, font: { size: 11 } }, grid: { color: gridColor, drawBorder: false }, border: { display: false } },
                            x: { ticks: { color: textColor, font: { size: 10 }, maxRotation: 45 }, grid: { display: false }, border: { display: false } },
                        },
                    };

                    @if($gaPageViews->isNotEmpty())
                    new Chart(document.getElementById('gaPageViewsChart'), {
                        type: 'bar',
                        data: {
                            labels: {!! json_encode($gaPageViews->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!},
                            datasets: [{ data: {!! json_encode($gaPageViews->pluck('metric_value')) !!}, backgroundColor: 'rgba(249, 115, 22, 0.7)', borderRadius: 6, borderSkipped: false }]
                        },
                        options: defaults,
                    });
                    @endif

                    @if($gaActiveUsers->isNotEmpty())
                    new Chart(document.getElementById('gaUsersChart'), {
                        type: 'line',
                        data: {
                            labels: {!! json_encode($gaActiveUsers->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!},
                            datasets: [{ data: {!! json_encode($gaActiveUsers->pluck('metric_value')) !!}, borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, 0.08)', fill: true, tension: 0.4, pointRadius: 3, borderWidth: 2.5 }]
                        },
                        options: defaults,
                    });
                    @endif
                });
            </script>
            @endpush
            @endif

            @endif

            <!-- ============ GOOGLE SEARCH CONSOLE ============ -->
            @if($integration === 'google_search_console')
            @php
                $gscClicks = collect($clicks ?? [])->reverse()->values();
                $gscImpressions = collect($impressions ?? [])->reverse()->values();
                $gscCtr = collect($metrics)->where('metric_name', 'ctr')->reverse()->values();
                $gscPosition = collect($metrics)->where('metric_name', 'avg_position')->reverse()->values();
            @endphp

            <div class="metric-grid mb-6">
                @foreach(['clicks' => 'Clicks', 'impressions' => 'Impressions', 'ctr' => 'CTR', 'avg_position' => 'Avg. Position'] as $mKey => $mLabel)
                    @php $val = collect($metrics)->where('metric_name', $mKey)->first(); @endphp
                    <div class="stat-card">
                        <div class="stat-icon bg-blue-100 dark:bg-blue-900/30 ring-blue-200 dark:ring-blue-800/50">
                            <span class="font-bold text-blue-600 dark:text-blue-400 text-xs">GSC</span>
                        </div>
                        <div class="stat-label">{{ $mLabel }}</div>
                        <div class="stat-value">{{ $val ? ($mKey === 'ctr' ? round($val['metric_value'] ?? 0, 2) . '%' : number_format($val['metric_value'] ?? 0)) : '—' }}</div>
                        <div class="stat-sub">{{ $val ? \Carbon\Carbon::parse($val['recorded_at'])->diffForHumans() : 'no data' }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                @if($gscClicks->isNotEmpty())
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Clicks Trend</h3>
                    <div class="h-56"><canvas id="gscClicksChart"></canvas></div>
                </div>
                @endif
                @if($gscImpressions->isNotEmpty())
                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Impressions Trend</h3>
                    <div class="h-56"><canvas id="gscImpressionsChart"></canvas></div>
                </div>
                @endif
            </div>

            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Metrics</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800">
                                <th class="px-5 py-3">Metric</th><th class="px-5 py-3">Value</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Recorded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($metrics as $m)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ ucwords(str_replace('_', ' ', $m['metric_name'])) }}</td>
                                <td class="px-5 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $m['metric_name'] === 'ctr' ? round($m['metric_value'] ?? 0, 2) . '%' : number_format($m['metric_value'] ?? 0) }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $m['unit'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ \Carbon\Carbon::parse($m['recorded_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($gscClicks->isNotEmpty() || $gscImpressions->isNotEmpty())
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#94a3b8' : '#64748b';
                    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';
                    const d = {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { color: textColor, font: { size: 11 } }, grid: { color: gridColor, drawBorder: false }, border: { display: false } }, x: { ticks: { color: textColor, font: { size: 10 }, maxRotation: 45 }, grid: { display: false }, border: { display: false } } },
                    };
                    @if($gscClicks->isNotEmpty())
                    new Chart(document.getElementById('gscClicksChart'), { type: 'bar', data: { labels: {!! json_encode($gscClicks->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!}, datasets: [{ data: {!! json_encode($gscClicks->pluck('metric_value')) !!}, backgroundColor: 'rgba(37, 99, 235, 0.7)', borderRadius: 6 }] }, options: d });
                    @endif
                    @if($gscImpressions->isNotEmpty())
                    new Chart(document.getElementById('gscImpressionsChart'), { type: 'line', data: { labels: {!! json_encode($gscImpressions->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!}, datasets: [{ data: {!! json_encode($gscImpressions->pluck('metric_value')) !!}, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.08)', fill: true, tension: 0.4, pointRadius: 3, borderWidth: 2.5 }] }, options: d });
                    @endif
                });
            </script>
            @endpush
            @endif
            @endif

            <!-- ============ CLOUDFLARE ============ -->
            @if($integration === 'cloudflare')
            @php
                $cfRequests = collect($metrics)->where('metric_name', 'requests')->reverse()->values();
                $cfPageViews = collect($metrics)->where('metric_name', 'page_views')->reverse()->values();
                $cfThreats = collect($metrics)->where('metric_name', 'threats_blocked')->reverse()->values();
                $cfBandwidth = collect($metrics)->where('metric_name', 'bandwidth_bytes')->reverse()->values();
                $cfUnique = collect($metrics)->where('metric_name', 'unique_visitors')->reverse()->values();
                $cfCache = collect($metrics)->where('metric_name', 'cache_hit_ratio_pct')->reverse()->values();
            @endphp

            <div class="metric-grid mb-6">
                @foreach([
                    'requests' => 'Total Requests', 'page_views' => 'Page Views',
                    'threats_blocked' => 'Threats Blocked', 'unique_visitors' => 'Unique Visitors',
                    'bandwidth_bytes' => 'Bandwidth', 'cache_hit_ratio_pct' => 'Cache Hit Ratio',
                    'security_events' => 'Security Events'
                ] as $mKey => $mLabel)
                    @php $val = collect($metrics)->where('metric_name', $mKey)->first(); @endphp
                    <div class="stat-card">
                        <div class="stat-icon bg-sky-100 dark:bg-sky-900/30 ring-sky-200 dark:ring-sky-800/50">
                            <span class="font-bold text-sky-600 dark:text-sky-400 text-xs">CF</span>
                        </div>
                        <div class="stat-label">{{ $mLabel }}</div>
                        <div class="stat-value">
                            @if(!$val) —
                            @elseif($mKey === 'bandwidth_bytes') {{ round($val['metric_value'] / 1024 / 1024, 1) }} MB
                            @elseif($mKey === 'cache_hit_ratio_pct') {{ round($val['metric_value'] ?? 0, 1) }}%
                            @else {{ number_format($val['metric_value'] ?? 0) }}
                            @endif
                        </div>
                        <div class="stat-sub">{{ $val ? \Carbon\Carbon::parse($val['recorded_at'])->diffForHumans() : 'no data' }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                @if($cfRequests->isNotEmpty())
                <div class="card p-5"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Requests</h3><div class="h-56"><canvas id="cfRequestsChart"></canvas></div></div>
                @endif
                @if($cfThreats->isNotEmpty())
                <div class="card p-5"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Threats Blocked</h3><div class="h-56"><canvas id="cfThreatsChart"></canvas></div></div>
                @endif
            </div>

            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Metrics</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800"><th class="px-5 py-3">Metric</th><th class="px-5 py-3">Value</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Recorded</th></tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($metrics as $m)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ ucwords(str_replace('_', ' ', $m['metric_name'])) }}</td>
                                <td class="px-5 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $m['metric_name'] === 'bandwidth_bytes' ? round(($m['metric_value'] ?? 0) / 1024 / 1024, 1) . ' MB' : number_format($m['metric_value'] ?? 0) }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $m['unit'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ \Carbon\Carbon::parse($m['recorded_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($cfRequests->isNotEmpty() || $cfThreats->isNotEmpty())
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const tc = isDark ? '#94a3b8' : '#64748b';
                    const gc = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';
                    const d = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: tc, font: { size: 11 } }, grid: { color: gc, drawBorder: false }, border: { display: false } }, x: { ticks: { color: tc, font: { size: 10 }, maxRotation: 45 }, grid: { display: false }, border: { display: false } } } };
                    @if($cfRequests->isNotEmpty())
                    new Chart(document.getElementById('cfRequestsChart'), { type: 'bar', data: { labels: {!! json_encode($cfRequests->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!}, datasets: [{ data: {!! json_encode($cfRequests->pluck('metric_value')) !!}, backgroundColor: 'rgba(14, 165, 233, 0.7)', borderRadius: 6 }] }, options: d });
                    @endif
                    @if($cfThreats->isNotEmpty())
                    new Chart(document.getElementById('cfThreatsChart'), { type: 'line', data: { labels: {!! json_encode($cfThreats->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!}, datasets: [{ data: {!! json_encode($cfThreats->pluck('metric_value')) !!}, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.08)', fill: true, tension: 0.4, pointRadius: 3, borderWidth: 2.5 }] }, options: d });
                    @endif
                });
            </script>
            @endpush
            @endif
            @endif

            <!-- ============ MICROSOFT CLARITY ============ -->
            @if($integration === 'microsoft_clarity')
            @php
                $mcSessions = collect($metrics)->where('metric_name', 'sessions')->reverse()->values();
                $mcPageViews = collect($metrics)->where('metric_name', 'page_views')->reverse()->values();
                $mcClicks = collect($metrics)->where('metric_name', 'clicks')->reverse()->values();
            @endphp

            <div class="metric-grid mb-6">
                @foreach(['sessions' => 'Sessions', 'page_views' => 'Page Views', 'clicks' => 'Clicks', 'click_through_rate' => 'CTR'] as $mKey => $mLabel)
                    @php $val = collect($metrics)->where('metric_name', $mKey)->first(); @endphp
                    <div class="stat-card">
                        <div class="stat-icon bg-cyan-100 dark:bg-cyan-900/30 ring-cyan-200 dark:ring-cyan-800/50">
                            <span class="font-bold text-cyan-600 dark:text-cyan-400 text-xs">MC</span>
                        </div>
                        <div class="stat-label">{{ $mLabel }}</div>
                        <div class="stat-value">{{ $val ? ($mKey === 'click_through_rate' ? round($val['metric_value'] ?? 0, 2) . '%' : number_format($val['metric_value'] ?? 0)) : '—' }}</div>
                        <div class="stat-sub">{{ $val ? \Carbon\Carbon::parse($val['recorded_at'])->diffForHumans() : 'no data' }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                @if($mcSessions->isNotEmpty())
                <div class="card p-5"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Sessions</h3><div class="h-56"><canvas id="mcSessionsChart"></canvas></div></div>
                @endif
                @if($mcPageViews->isNotEmpty())
                <div class="card p-5"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Page Views</h3><div class="h-56"><canvas id="mcPageViewsChart"></canvas></div></div>
                @endif
            </div>

            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Metrics</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800"><th class="px-5 py-3">Metric</th><th class="px-5 py-3">Value</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Recorded</th></tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($metrics as $m)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ ucwords(str_replace('_', ' ', $m['metric_name'])) }}</td>
                                <td class="px-5 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $m['metric_name'] === 'click_through_rate' ? round($m['metric_value'] ?? 0, 2) . '%' : number_format($m['metric_value'] ?? 0) }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $m['unit'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ \Carbon\Carbon::parse($m['recorded_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($mcSessions->isNotEmpty() || $mcPageViews->isNotEmpty())
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const tc = isDark ? '#94a3b8' : '#64748b';
                    const gc = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';
                    const d = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: tc, font: { size: 11 } }, grid: { color: gc, drawBorder: false }, border: { display: false } }, x: { ticks: { color: tc, font: { size: 10 }, maxRotation: 45 }, grid: { display: false }, border: { display: false } } } };
                    @if($mcSessions->isNotEmpty())
                    new Chart(document.getElementById('mcSessionsChart'), { type: 'bar', data: { labels: {!! json_encode($mcSessions->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!}, datasets: [{ data: {!! json_encode($mcSessions->pluck('metric_value')) !!}, backgroundColor: 'rgba(6, 182, 212, 0.7)', borderRadius: 6 }] }, options: d });
                    @endif
                    @if($mcPageViews->isNotEmpty())
                    new Chart(document.getElementById('mcPageViewsChart'), { type: 'line', data: { labels: {!! json_encode($mcPageViews->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!}, datasets: [{ data: {!! json_encode($mcPageViews->pluck('metric_value')) !!}, borderColor: '#06b6d4', backgroundColor: 'rgba(6, 182, 212, 0.08)', fill: true, tension: 0.4, pointRadius: 3, borderWidth: 2.5 }] }, options: d });
                    @endif
                });
            </script>
            @endpush
            @endif
            @endif

            <!-- ============ STRIPE ============ -->
            @if($integration === 'stripe')
            @php
                $stripeMrs = collect($mrr ?? [])->reverse()->values();
            @endphp

            <div class="metric-grid mb-6">
                @foreach(['mrr' => 'MRR', 'successful_charges' => 'Successful Charges', 'failed_charges' => 'Failed Charges', 'refunds' => 'Refunds', 'active_subscriptions' => 'Active Subscriptions', 'disputes_open' => 'Open Disputes'] as $mKey => $mLabel)
                    @php $val = collect($metrics)->where('metric_name', $mKey)->first(); @endphp
                    <div class="stat-card">
                        <div class="stat-icon bg-violet-100 dark:bg-violet-900/30 ring-violet-200 dark:ring-violet-800/50">
                            <span class="font-bold text-violet-600 dark:text-violet-400 text-xs">ST</span>
                        </div>
                        <div class="stat-label">{{ $mLabel }}</div>
                        <div class="stat-value {{ $mKey === 'failed_charges' || $mKey === 'disputes_open' ? ($val && ($val['metric_value'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : '') : '' }}">
                            {{ $val ? ($mKey === 'mrr' ? '$' . number_format($val['metric_value'] ?? 0, 2) : number_format($val['metric_value'] ?? 0)) : '—' }}
                        </div>
                        <div class="stat-sub">{{ $val ? \Carbon\Carbon::parse($val['recorded_at'])->diffForHumans() : 'no data' }}</div>
                    </div>
                @endforeach
            </div>

            @if($stripeMrs->isNotEmpty())
            <div class="card p-5 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">MRR Trend</h3>
                <div class="h-56"><canvas id="stripeMrrChart"></canvas></div>
            </div>
            @endif

            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800"><h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Metrics</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800"><th class="px-5 py-3">Metric</th><th class="px-5 py-3">Value</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Recorded</th></tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($metrics as $m)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ ucwords(str_replace('_', ' ', $m['metric_name'])) }}</td>
                                <td class="px-5 py-3 font-mono text-gray-700 dark:text-gray-300">{{ $m['metric_name'] === 'mrr' ? '$' . number_format($m['metric_value'] ?? 0, 2) : number_format($m['metric_value'] ?? 0) }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $m['unit'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ \Carbon\Carbon::parse($m['recorded_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($stripeMrs->isNotEmpty())
            @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const tc = isDark ? '#94a3b8' : '#64748b';
                    const gc = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';
                    new Chart(document.getElementById('stripeMrrChart'), { type: 'line', data: { labels: {!! json_encode($stripeMrs->pluck('recorded_at')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d H:i'))) !!}, datasets: [{ data: {!! json_encode($stripeMrs->pluck('metric_value')) !!}, borderColor: '#8b5cf6', backgroundColor: 'rgba(139, 92, 246, 0.08)', fill: true, tension: 0.4, pointRadius: 3, borderWidth: 2.5 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: tc, font: { size: 11 }, callback: v => '$' + v }, grid: { color: gc, drawBorder: false }, border: { display: false } }, x: { ticks: { color: tc, font: { size: 10 }, maxRotation: 45 }, grid: { display: false }, border: { display: false } } } } });
                });
            </script>
            @endpush
            @endif
            @endif

            <!-- ============ GITHUB / GITLAB ============ -->
            @if($integration === 'github')
            @php
                $ghDeployments = $deployments ?? [];
                $ghWorkflows = $workflows ?? [];
                $ghReleases = $releases ?? [];
            @endphp

            <!-- Deployment Status -->
            <div class="card mb-6">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Deployments</h3>
                </div>
                <div class="overflow-x-auto">
                    @if(empty($ghDeployments))
                    <div class="p-8 text-center text-sm text-gray-400 dark:text-gray-500">No deployment data yet.</div>
                    @else
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800"><th class="px-5 py-3">Ref</th><th class="px-5 py-3">Environment</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Creator</th><th class="px-5 py-3">Recorded</th></tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($ghDeployments as $dep)
                            @php $dims = $dep['dimensions'] ?? []; @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $dims['ref'] ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($dims['environment'] ?? '') === 'production' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">{{ $dims['environment'] ?? '—' }}</span></td>
                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($dims['status'] ?? '') === 'success' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : (($dims['status'] ?? '') === 'failure' || ($dims['status'] ?? '') === 'error' ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400') }}">{{ $dims['status'] ?? '—' }}</span></td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ $dims['creator'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ \Carbon\Carbon::parse($dep['recorded_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>

            <!-- Workflow Runs -->
            <div class="card mb-6">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Workflow Runs</h3>
                </div>
                <div class="overflow-x-auto">
                    @if(empty($ghWorkflows))
                    <div class="p-8 text-center text-sm text-gray-400 dark:text-gray-500">No workflow run data yet.</div>
                    @else
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800"><th class="px-5 py-3">Name</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Conclusion</th><th class="px-5 py-3">Branch</th><th class="px-5 py-3">Actor</th></tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($ghWorkflows as $wf)
                            @php $dims = $wf['dimensions'] ?? []; @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100 text-xs">{{ $dims['name'] ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($dims['status'] ?? '') === 'completed' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' }}">{{ $dims['status'] ?? '—' }}</span></td>
                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($dims['conclusion'] ?? '') === 'success' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' : (($dims['conclusion'] ?? '') === 'failure' ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400') }}">{{ $dims['conclusion'] ?? '—' }}</span></td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">{{ $dims['branch'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ $dims['actor'] ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>

            <!-- Releases -->
            <div class="card mb-6">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Releases</h3>
                </div>
                <div class="overflow-x-auto">
                    @if(empty($ghReleases))
                    <div class="p-8 text-center text-sm text-gray-400 dark:text-gray-500">No release data yet.</div>
                    @else
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800"><th class="px-5 py-3">Tag</th><th class="px-5 py-3">Name</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Published</th></tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($ghReleases as $rel)
                            @php $dims = $rel['dimensions'] ?? []; @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $dims['tag_name'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $dims['name'] ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($dims['prerelease'] ?? false) ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' }}">{{ ($dims['prerelease'] ?? false) ? 'Pre-release' : 'Release' }}</span></td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ isset($dims['published_at']) ? \Carbon\Carbon::parse($dims['published_at'])->diffForHumans() : '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
            @endif

            <!-- ============ EMAIL SERVICES ============ -->
            @if($integration === 'email_service')
            <div class="metric-grid mb-6">
                @foreach(['sent' => 'Emails Sent', 'delivered' => 'Delivered', 'opened' => 'Opened', 'clicks' => 'Link Clicks', 'bounced' => 'Bounced', 'complaints' => 'Complaints'] as $mKey => $mLabel)
                    @php $val = collect($metrics)->where('metric_name', $mKey)->first(); @endphp
                    <div class="stat-card">
                        <div class="stat-icon bg-pink-100 dark:bg-pink-900/30 ring-pink-200 dark:ring-pink-800/50">
                            <span class="font-bold text-pink-600 dark:text-pink-400 text-xs">@</span>
                        </div>
                        <div class="stat-label">{{ $mLabel }}</div>
                        <div class="stat-value {{ ($mKey === 'bounced' || $mKey === 'complaints') && $val && ($val['metric_value'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ $val ? number_format($val['metric_value'] ?? 0) : '—' }}</div>
                        <div class="stat-sub">{{ $val ? \Carbon\Carbon::parse($val['recorded_at'])->diffForHumans() : 'no data' }}</div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Provider: {{ $providerLabel ?? 'Email' }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-50 dark:border-gray-800"><th class="px-5 py-3">Metric</th><th class="px-5 py-3">Value</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Recorded</th></tr></thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($metrics as $m)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ ucwords(str_replace('_', ' ', $m['metric_name'])) }}</td>
                                <td class="px-5 py-3 font-mono text-gray-700 dark:text-gray-300">{{ number_format($m['metric_value'] ?? 0) }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $m['unit'] ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ \Carbon\Carbon::parse($m['recorded_at'])->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @endif {{-- end has metrics --}}
        </div>
    </div>
</x-app-layout>