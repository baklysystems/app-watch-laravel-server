<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">
                    @if($viewMode === 'all')
                        All Projects
                        <span class="ml-2 text-sm font-medium text-purple-500 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/50 px-2.5 py-0.5 rounded-full">{{ $allProjects->count() }} projects</span>
                    @elseif($project)
                        {{ $project->name }}
                        <span class="ml-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">{{ $project->environment }}</span>
                    @else
                        Dashboard
                    @endif
                </h2>
                @if($viewMode === 'all')
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Aggregated insights across all monitored applications</p>
                @elseif($project)
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Overview of your application's health and performance</p>
                @endif
            </div>
            <div class="flex gap-2 items-center">
                @if($projects->isNotEmpty())
                <form method="GET" class="flex gap-2">
                    <select name="project_id" onchange="this.form.submit()" class="select-premium !w-auto">
                        @if($isSuperAdmin)
                            <option value="all" {{ $viewMode === 'all' ? 'selected' : '' }}>🌐 All Projects</option>
                        @endif
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ $viewMode === 'single' && $project && $p->id == $project->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300 text-sm font-medium flex items-center gap-2" data-flash>
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
            @endif

            @if($projects->isEmpty())
            <!-- Empty State -->
            <div class="card p-12 lg:p-16 text-center">
                <div class="mx-auto w-20 h-20 rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-950 dark:to-brand-900 flex items-center justify-center mb-6 ring-1 ring-brand-200 dark:ring-brand-800">
                    <svg class="h-10 w-10 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Welcome to Appswatch</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-md mx-auto">Create your first project to start monitoring your Laravel applications with powerful insights and real-time alerts.</p>
                <form method="POST" action="{{ route('projects.create') }}" class="mt-8 max-w-sm mx-auto space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5 text-left">Project Name</label>
                        <input type="text" name="name" required class="input-premium" placeholder="e.g., My Laravel App">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5 text-left">Environment</label>
                        <select name="environment" required class="select-premium">
                            <option value="production">Production</option>
                            <option value="staging">Staging</option>
                            <option value="local">Local</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary w-full" data-ripple>Create Project</button>
                </form>
            </div>
            @elseif($viewMode === 'all')
            <!-- ==================== SUPER ADMIN: ALL PROJECTS VIEW ==================== -->

            <!-- Aggregated Stats Grid -->
            <div class="metric-grid mb-6">
                <div class="stat-card">
                    <div class="stat-icon bg-red-50 dark:bg-red-950/50 ring-red-200 dark:ring-red-800/50">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div class="stat-label">Exceptions (All)</div>
                    <div class="stat-value">{{ number_format($stats['total_exceptions']) }}</div>
                    <div class="stat-sub">
                        <span class="text-red-500 font-medium">{{ $stats['unresolved_exceptions'] }} unresolved</span>
                        &middot; {{ $stats['critical_exceptions'] }} critical
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-emerald-50 dark:bg-emerald-950/50 ring-emerald-200 dark:ring-emerald-800/50">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.707.293V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="stat-label">Logs (24h)</div>
                    <div class="stat-value">{{ number_format($stats['log_volume']) }}</div>
                    <div class="stat-sub">entries across all projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-amber-50 dark:bg-amber-950/50 ring-amber-200 dark:ring-amber-800/50">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Queue Failures</div>
                    <div class="stat-value {{ $stats['queue_failures'] > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ number_format($stats['queue_failures']) }}</div>
                    <div class="stat-sub">last 24 hours, all projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-brand-50 dark:bg-brand-950/50 ring-brand-200 dark:ring-brand-800/50">
                        <svg class="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="stat-label">Avg Response</div>
                    <div class="stat-value">{{ $stats['avg_response_time'] }} <span class="text-sm font-normal text-gray-400">ms</span></div>
                    <div class="stat-sub">{{ number_format($stats['total_requests']) }} requests</div>
                </div>
            </div>

            <!-- Aggregated Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Exception Trend (All Projects)</h3>
                        <span class="badge-gray text-[10px]">Last 7 Days</span>
                    </div>
                    <div class="h-56">
                        <canvas id="exceptionChart"></canvas>
                    </div>
                </div>
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Avg Response Time (All Projects)</h3>
                        <span class="badge-gray text-[10px]">Last 7 Days</span>
                    </div>
                    <div class="h-56">
                        <canvas id="responseChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Per-Project Insight Cards -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Per‑Project Snapshot</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($allProjects as $p)
                        @php
                            $pSince = now()->subDay();
                            $pExceptions = App\Models\AppException::where('project_id', $p->id)->count();
                            $pUnresolved = App\Models\AppException::where('project_id', $p->id)->where('status', 'unresolved')->count();
                            $pCritical = App\Models\AppException::where('project_id', $p->id)->where('severity', 'critical')->count();
                            $pLogs = App\Models\LogEntry::where('project_id', $p->id)->where('occurred_at', '>=', $pSince)->count();
                            $pQueueFailures = App\Models\QueueJob::where('project_id', $p->id)->where('status', 'failed')->where('created_at', '>=', $pSince)->count();
                            $pAvgResp = round(App\Models\HttpRequest::where('project_id', $p->id)->where('occurred_at', '>=', $pSince)->avg('duration_ms') ?? 0, 2);
                        @endphp
                        <a href="{{ route('dashboard', ['project_id' => $p->id]) }}" class="card p-4 hover:ring-2 hover:ring-brand-200 dark:hover:ring-brand-800 transition-all cursor-pointer group">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $p->name }}</h4>
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-full
                                    @if($p->environment === 'production') bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-800/50
                                    @elseif($p->environment === 'staging') bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400
                                    @else bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 @endif
                                ">{{ $p->environment }}</span>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Exceptions</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($pExceptions) }}
                                        @if($pUnresolved > 0) <span class="text-red-500">({{ $pUnresolved }} open)</span> @endif
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Logs (24h)</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($pLogs) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Queue Failures</span>
                                    <span class="font-semibold {{ $pQueueFailures > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $pQueueFailures }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Avg Response</span>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $pAvgResp }} ms</span>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-50 dark:border-gray-800 flex justify-end">
                                <span class="text-[11px] text-brand-500 font-medium group-hover:underline">View details &rarr;</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Top Exceptions (24h) -->
            @if(isset($topExceptions) && $topExceptions->isNotEmpty())
            <div class="card mb-6">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">🔥 Top Exceptions <span class="text-xs font-normal text-gray-400 ml-1">Last 24 Hours</span></h3>
                    <span class="badge-gray text-[10px]">By Occurrence Count</span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @foreach($topExceptions as $topEx)
                    @php
                        $topExProject = $allProjects->firstWhere('id', $topEx->project_id);
                    @endphp
                    <a href="{{ route('exceptions.show', $topEx->id) }}" class="px-5 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors flex items-center gap-4 group">
                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold
                            @if($topEx->occurrence_count >= 50) bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400 ring-1 ring-red-200 dark:ring-red-800/50
                            @elseif($topEx->occurrence_count >= 10) bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800/50
                            @else bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 ring-1 ring-gray-200 dark:ring-gray-700
                            @endif
                        ">{{ $topEx->occurrence_count }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-brand-600 dark:group-hover:text-brand-400 truncate transition-colors">{{ class_basename($topEx->class) }}</span>
                                <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold
                                    @switch($topEx->severity)
                                        @case('critical') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('error') bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 @break
                                        @case('warning') bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">{{ strtoupper(substr($topEx->severity, 0, 3)) }}</span>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ \Illuminate\Support\Str::limit($topEx->message, 100) }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                @if($viewMode === 'all' && $topExProject)
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">{{ $topExProject->name }}</span>
                                <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                @endif
                                <span class="text-[11px] font-mono text-gray-400 file-path">{{ basename($topEx->file) }}:{{ $topEx->line }}</span>
                                <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                <span class="text-[11px] text-gray-400">{{ $topEx->last_seen_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-brand-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Recent Activity Feed (with project labels) -->
            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Activity (All Projects)</h3>
                    <span class="badge-gray text-[10px]">Live</span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800 max-h-[32rem] overflow-y-auto">
                    @forelse($recentActivity as $activity)
                    @php
                        $activityProject = $allProjects->firstWhere('id', $activity['project_id']);
                    @endphp
                    <div class="px-5 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors animate-fade-in-up">
                        @if($activity['type'] === 'exception')
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold
                                    @switch($activity['data']->severity)
                                        @case('critical') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('error') bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 @break
                                        @case('warning') bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">{{ strtoupper(substr($activity['data']->severity, 0, 3)) }}</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('exceptions.show', $activity['data']->id) }}" class="text-sm text-gray-900 dark:text-gray-100 hover:text-brand-600 dark:hover:text-brand-400 truncate block font-medium transition-colors">
                                        {{ $activity['data']->class }}: {{ \Illuminate\Support\Str::limit($activity['data']->message, 80) }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($activityProject)
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">{{ $activityProject->name }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        @endif
                                        <span class="text-xs text-gray-400">{{ $activity['time']->diffForHumans() }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span class="text-xs font-mono text-gray-400 file-path">{{ basename($activity['data']->file) }}:{{ $activity['data']->line }}</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($activity['type'] === 'job')
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold
                                    @switch($activity['data']->status)
                                        @case('failed') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 @break
                                        @case('processing') bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">JOB</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('queues.show', $activity['data']->id) }}" class="text-sm text-gray-900 dark:text-gray-100 hover:text-brand-600 dark:hover:text-brand-400 truncate block font-medium transition-colors">
                                        {{ $activity['data']->job_name }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($activityProject)
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">{{ $activityProject->name }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        @endif
                                        <span class="text-xs text-gray-400">{{ ucfirst($activity['data']->status) }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span class="text-xs text-gray-400">{{ $activity['time']->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($activity['type'] === 'task')
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold
                                    @switch($activity['data']->status)
                                        @case('failed') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">SCH</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('schedules.index', ['project_id' => $activity['project_id']]) }}" class="text-sm text-gray-900 dark:text-gray-100 hover:text-brand-600 dark:hover:text-brand-400 truncate block font-medium font-mono text-xs transition-colors">
                                        {{ $activity['data']->command }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($activityProject)
                                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">{{ $activityProject->name }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        @endif
                                        <span class="text-xs text-gray-400">{{ ucfirst($activity['data']->status) }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span class="text-xs text-gray-400">{{ $activity['time']->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @empty
                    <div class="empty-state py-12">
                        <div class="empty-state-icon">
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                        <p class="empty-state-title">No activity yet</p>
                        <p class="empty-state-desc">Data will appear here as your apps send telemetry.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            @else
            <!-- ==================== SINGLE PROJECT VIEW ==================== -->
            <!-- Stats Grid -->
            <div class="metric-grid mb-6">
                <div class="stat-card group cursor-pointer" onclick="window.location='{{ route('exceptions.index', ['project_id' => $project->id]) }}'">
                    <div class="stat-icon bg-red-50 dark:bg-red-950/50 ring-red-200 dark:ring-red-800/50 group-hover:shadow-glow-red transition-shadow">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <div class="stat-label">Exceptions</div>
                    <div class="stat-value">{{ number_format($stats['total_exceptions']) }}</div>
                    <div class="stat-sub">
                        <span class="text-red-500 font-medium">{{ $stats['unresolved_exceptions'] }} unresolved</span>
                        &middot; {{ $stats['critical_exceptions'] }} critical
                    </div>
                </div>
                <div class="stat-card group cursor-pointer" onclick="window.location='{{ route('logs.index', ['project_id' => $project->id]) }}'">
                    <div class="stat-icon bg-emerald-50 dark:bg-emerald-950/50 ring-emerald-200 dark:ring-emerald-800/50 group-hover:shadow-glow-green transition-shadow">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.707.293V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="stat-label">Logs (24h)</div>
                    <div class="stat-value">{{ number_format($stats['log_volume']) }}</div>
                    <div class="stat-sub">entries ingested</div>
                </div>
                <div class="stat-card group cursor-pointer" onclick="window.location='{{ route('queues.index', ['project_id' => $project->id]) }}'">
                    <div class="stat-icon bg-amber-50 dark:bg-amber-950/50 ring-amber-200 dark:ring-amber-800/50 group-hover:shadow-glow-amber transition-shadow">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="stat-label">Queue Failures</div>
                    <div class="stat-value {{ $stats['queue_failures'] > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ number_format($stats['queue_failures']) }}</div>
                    <div class="stat-sub">last 24 hours</div>
                </div>
                <div class="stat-card group cursor-pointer" onclick="window.location='{{ route('performance.requests', ['project_id' => $project->id]) }}'">
                    <div class="stat-icon bg-brand-50 dark:bg-brand-950/50 ring-brand-200 dark:ring-brand-800/50 group-hover:shadow-glow transition-shadow">
                        <svg class="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="stat-label">Avg Response</div>
                    <div class="stat-value">{{ $stats['avg_response_time'] }} <span class="text-sm font-normal text-gray-400">ms</span></div>
                    <div class="stat-sub">{{ number_format($stats['total_requests']) }} requests</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Exception Trend</h3>
                        <span class="badge-gray text-[10px]">Last 7 Days</span>
                    </div>
                    <div class="h-56">
                        <canvas id="exceptionChart"></canvas>
                    </div>
                </div>
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Avg Response Time</h3>
                        <span class="badge-gray text-[10px]">Last 7 Days</span>
                    </div>
                    <div class="h-56">
                        <canvas id="responseChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="card p-5">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-[0.075em] mb-4">Uptime</h3>
                    @if($uptimeStatus)
                        <div class="flex items-center gap-3">
                            <span class="pulse-dot-{{ $uptimeStatus->metric_value ? 'green' : 'red' }}"></span>
                            <span class="text-2xl font-bold {{ $uptimeStatus->metric_value ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $uptimeStatus->metric_value ? 'Up' : 'Down' }}
                            </span>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No uptime data yet.</p>
                    @endif
                </div>
                <div class="card p-5">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-[0.075em] mb-4">SSL Certificate</h3>
                    @if($sslStatus)
                        @php $sslDays = (int)$sslStatus->metric_value; @endphp
                        <div class="text-2xl font-bold {{ $sslDays <= 30 ? 'text-red-600 dark:text-red-400' : ($sslDays <= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                            {{ $sslDays }} <span class="text-lg">days</span>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">until expiry</div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No SSL data yet.</p>
                    @endif
                </div>
                <div class="card p-5">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-[0.075em] mb-4">Server Resources</h3>
                    @if($serverMetrics->isNotEmpty())
                        @php
                            $cpu = $serverMetrics->firstWhere('metric_name', 'cpu_load_1m');
                            $mem = $serverMetrics->firstWhere('metric_name', 'memory_usage_pct');
                            $disk = $serverMetrics->firstWhere('metric_name', 'disk_usage_pct');
                        @endphp
                        <div class="space-y-3">
                            @if($cpu)
                            <div>
                                <div class="flex justify-between text-xs mb-1"><span class="text-gray-400">CPU</span><span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">{{ round($cpu->metric_value, 1) }} load</span></div>
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-brand-500 rounded-full transition-all duration-500" style="width: {{ min(round($cpu->metric_value / 4 * 100), 100) }}%"></div>
                                </div>
                            </div>
                            @endif
                            @if($mem)
                            <div>
                                <div class="flex justify-between text-xs mb-1"><span class="text-gray-400">Memory</span><span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">{{ $mem->metric_value }}%</span></div>
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full {{ $mem->metric_value > 80 ? 'bg-red-500' : ($mem->metric_value > 60 ? 'bg-amber-500' : 'bg-emerald-500') }} rounded-full transition-all duration-500" style="width: {{ $mem->metric_value }}%"></div>
                                </div>
                            </div>
                            @endif
                            @if($disk)
                            <div>
                                <div class="flex justify-between text-xs mb-1"><span class="text-gray-400">Disk</span><span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">{{ $disk->metric_value }}%</span></div>
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full {{ $disk->metric_value > 80 ? 'bg-red-500' : ($disk->metric_value > 60 ? 'bg-amber-500' : 'bg-emerald-500') }} rounded-full transition-all duration-500" style="width: {{ $disk->metric_value }}%"></div>
                                </div>
                            </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No server metrics yet.</p>
                    @endif
                </div>
            </div>

            <!-- Integration Health Cards -->
            @if(isset($mysqlHealth) || isset($backupMetrics) || isset($domainExpiry))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- MySQL Health -->
                <div class="card p-5">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-[0.075em] mb-4">MySQL Health</h3>
                    @if(isset($mysqlHealth) && $mysqlHealth->isNotEmpty())
                        @php
                            $connections = $mysqlHealth->firstWhere('metric_name', 'connection_saturation_pct');
                            $replicationLag = $mysqlHealth->firstWhere('metric_name', 'replication_lag_seconds');
                        @endphp
                        <div class="space-y-3">
                            @if($connections)
                            <div>
                                <div class="flex justify-between text-xs mb-1"><span class="text-gray-400">Connections</span><span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">{{ round($connections->metric_value, 1) }}%</span></div>
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full {{ $connections->metric_value > 80 ? 'bg-red-500' : ($connections->metric_value > 60 ? 'bg-amber-500' : 'bg-emerald-500') }} rounded-full transition-all duration-500" style="width: {{ $connections->metric_value }}%"></div>
                                </div>
                            </div>
                            @endif
                            @if($replicationLag)
                            <div>
                                <div class="flex justify-between text-xs mb-1"><span class="text-gray-400">Replication Lag</span><span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">{{ round($replicationLag->metric_value, 1) }}s</span></div>
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full {{ $replicationLag->metric_value > 10 ? 'bg-red-500' : ($replicationLag->metric_value > 5 ? 'bg-amber-500' : 'bg-emerald-500') }} rounded-full transition-all duration-500" style="width: {{ min($replicationLag->metric_value / 15 * 100, 100) }}%"></div>
                                </div>
                            </div>
                            @endif
                            @if(!$connections && !$replicationLag)
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">Collecting metrics...</p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No MySQL metrics yet. Enable in Settings.</p>
                    @endif
                </div>

                <!-- Database Backups -->
                <div class="card p-5">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-[0.075em] mb-4">Database Backups</h3>
                    @if(isset($backupMetrics) && $backupMetrics->isNotEmpty())
                        @php
                            $lastBackup = $backupMetrics->firstWhere('metric_name', 'backup_success');
                            $backupSize = $backupMetrics->firstWhere('metric_name', 'backup_size_bytes');
                        @endphp
                        @if($lastBackup)
                            <div class="flex items-center gap-3">
                                <span class="pulse-dot-{{ $lastBackup->metric_value ? 'green' : 'red' }}"></span>
                                <span class="text-2xl font-bold {{ $lastBackup->metric_value ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $lastBackup->metric_value ? 'OK' : 'Failed' }}
                                </span>
                            </div>
                            @if($backupSize)
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ round($backupSize->metric_value / 1024 / 1024, 1) }} MB</div>
                            @endif
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $lastBackup->recorded_at->diffForHumans() }}</div>
                        @endif
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No backup history yet. Enable in Settings.</p>
                    @endif
                </div>

                <!-- Domain Expiry -->
                <div class="card p-5">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-[0.075em] mb-4">Domain Expiry</h3>
                    @if(isset($domainExpiry))
                        @php $daysRemaining = (int)$domainExpiry->metric_value; @endphp
                        <div class="text-2xl font-bold {{ $daysRemaining <= 30 ? 'text-red-600 dark:text-red-400' : ($daysRemaining <= 90 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                            {{ $daysRemaining }} <span class="text-lg">days</span>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">until domain expires</div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No domain data yet. Enable in Settings.</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Top Exceptions (24h) -->
            @if(isset($topExceptions) && $topExceptions->isNotEmpty())
            <div class="card mb-6">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">🔥 Top Exceptions <span class="text-xs font-normal text-gray-400 ml-1">Last 24 Hours</span></h3>
                    <span class="badge-gray text-[10px]">By Occurrence Count</span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800">
                    @foreach($topExceptions as $topEx)
                    <a href="{{ route('exceptions.show', $topEx->id) }}" class="px-5 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors flex items-center gap-4 group">
                        <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-lg font-bold
                            @if($topEx->occurrence_count >= 50) bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400 ring-1 ring-red-200 dark:ring-red-800/50
                            @elseif($topEx->occurrence_count >= 10) bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800/50
                            @else bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 ring-1 ring-gray-200 dark:ring-gray-700
                            @endif
                        ">{{ $topEx->occurrence_count }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 group-hover:text-brand-600 dark:group-hover:text-brand-400 truncate transition-colors">{{ class_basename($topEx->class) }}</span>
                                <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold
                                    @switch($topEx->severity)
                                        @case('critical') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('error') bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 @break
                                        @case('warning') bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">{{ strtoupper(substr($topEx->severity, 0, 3)) }}</span>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ \Illuminate\Support\Str::limit($topEx->message, 100) }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] font-mono text-gray-400 file-path">{{ basename($topEx->file) }}:{{ $topEx->line }}</span>
                                <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                <span class="text-[11px] text-gray-400">{{ $topEx->last_seen_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-brand-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Recent Activity Feed -->
            <div class="card">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Activity</h3>
                    <span class="badge-gray text-[10px]">Live</span>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800 max-h-[32rem] overflow-y-auto">
                    @forelse($recentActivity as $activity)
                    <div class="px-5 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors animate-fade-in-up">
                        @if($activity['type'] === 'exception')
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold
                                    @switch($activity['data']->severity)
                                        @case('critical') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('error') bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 @break
                                        @case('warning') bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">{{ strtoupper(substr($activity['data']->severity, 0, 3)) }}</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('exceptions.show', $activity['data']->id) }}" class="text-sm text-gray-900 dark:text-gray-100 hover:text-brand-600 dark:hover:text-brand-400 truncate block font-medium transition-colors">
                                        {{ $activity['data']->class }}: {{ \Illuminate\Support\Str::limit($activity['data']->message, 80) }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-400">{{ $activity['time']->diffForHumans() }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span class="text-xs font-mono text-gray-400 file-path">{{ basename($activity['data']->file) }}:{{ $activity['data']->line }}</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($activity['type'] === 'job')
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold
                                    @switch($activity['data']->status)
                                        @case('failed') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 @break
                                        @case('processing') bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">JOB</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('queues.show', $activity['data']->id) }}" class="text-sm text-gray-900 dark:text-gray-100 hover:text-brand-600 dark:hover:text-brand-400 truncate block font-medium transition-colors">
                                        {{ $activity['data']->job_name }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-400">{{ ucfirst($activity['data']->status) }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span class="text-xs text-gray-400">{{ $activity['time']->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($activity['type'] === 'task')
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold
                                    @switch($activity['data']->status)
                                        @case('failed') bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800/50 @break
                                        @case('completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 @break
                                        @default bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400
                                    @endswitch
                                ">SCH</span>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('schedules.index', ['project_id' => $project->id]) }}" class="text-sm text-gray-900 dark:text-gray-100 hover:text-brand-600 dark:hover:text-brand-400 truncate block font-medium font-mono text-xs transition-colors">
                                        {{ $activity['data']->command }}
                                    </a>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-400">{{ ucfirst($activity['data']->status) }}</span>
                                        <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                                        <span class="text-xs text-gray-400">{{ $activity['time']->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @empty
                    <div class="empty-state py-12">
                        <div class="empty-state-icon">
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                        <p class="empty-state-title">No activity yet</p>
                        <p class="empty-state-desc">Data will appear here as your apps send telemetry.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </div>

    @if(($viewMode === 'all' || ($project && $projects->isNotEmpty())) && !empty($exceptionTrend) && !empty($requestTrend))
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';

            const chartDefaults = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#475569',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 10,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor, font: { size: 11 }, padding: 8 },
                        grid: { color: gridColor, drawBorder: false },
                        border: { display: false },
                    },
                    x: {
                        ticks: { color: textColor, font: { size: 11 }, padding: 8, maxRotation: 0 },
                        grid: { display: false },
                        border: { display: false },
                    },
                },
            };

            // Exception Chart
            const excCtx = document.getElementById('exceptionChart');
            if (excCtx) {
                new Chart(excCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(array_column($exceptionTrend, 'date')) !!},
                        datasets: [{
                            data: {!! json_encode(array_column($exceptionTrend, 'count')) !!},
                            backgroundColor: 'rgba(99, 102, 241, 0.7)',
                            hoverBackgroundColor: '#6366f1',
                            borderWidth: 0,
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: { ...chartDefaults },
                });
            }

            // Response Time Chart
            const resCtx = document.getElementById('responseChart');
            if (resCtx) {
                new Chart(resCtx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(array_column($requestTrend, 'date')) !!},
                        datasets: [{
                            data: {!! json_encode(array_column($requestTrend, 'avg_ms')) !!},
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#10b981',
                            borderWidth: 2.5,
                        }]
                    },
                    options: {
                        ...chartDefaults,
                        scales: {
                            ...chartDefaults.scales,
                            y: {
                                ...chartDefaults.scales.y,
                                ticks: { ...chartDefaults.scales.y.ticks, callback: v => v + ' ms' },
                            },
                        },
                    },
                });
            }
        });
    </script>
    @endpush
    @endif
</x-app-layout>