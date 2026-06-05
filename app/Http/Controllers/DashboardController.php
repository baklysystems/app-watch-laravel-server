<?php

namespace App\Http\Controllers;

use App\Models\AppException;
use App\Models\HttpRequest;
use App\Models\IntegrationMetric;
use App\Models\LogEntry;
use App\Models\Project;
use App\Models\QueueJob;
use App\Models\ScheduledTask;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $projectId = $request->input('project_id', session('current_project_id'));

        // Super admins see all active projects; regular users see their own projects
        if ($isSuperAdmin) {
            $projects = Project::where('is_active', true)->get();
        } else {
            $projects = Project::where('is_active', true)
                ->where('user_id', $user->id)
                ->get();
        }

        if ($projects->isEmpty()) {
            return view('dashboard', [
                'projects' => collect(),
                'project' => null,
                'stats' => [],
                'recentActivity' => collect(),
                'exceptionTrend' => [],
                'requestTrend' => [],
                'isSuperAdmin' => $isSuperAdmin,
                'viewMode' => 'single',
                'allProjects' => collect(),
            ]);
        }

        // Determine view mode: 'all' for super admin viewing all projects, 'single' otherwise
        $viewMode = ($isSuperAdmin && $projectId === 'all') ? 'all' : 'single';

        if ($viewMode === 'all') {
            // --- Super Admin: Aggregated view across all projects ---
            $since = now()->subDay();

            $allProjectIds = $projects->pluck('id')->toArray();

            $stats = [
                'total_exceptions' => AppException::whereIn('project_id', $allProjectIds)->count(),
                'new_exceptions_today' => AppException::whereIn('project_id', $allProjectIds)
                    ->where('first_seen_at', '>=', $since)->count(),
                'unresolved_exceptions' => AppException::whereIn('project_id', $allProjectIds)
                    ->where('status', 'unresolved')->count(),
                'critical_exceptions' => AppException::whereIn('project_id', $allProjectIds)
                    ->where('severity', 'critical')->count(),
                'log_volume' => LogEntry::whereIn('project_id', $allProjectIds)
                    ->where('occurred_at', '>=', $since)->count(),
                'queue_failures' => QueueJob::whereIn('project_id', $allProjectIds)
                    ->where('status', 'failed')->where('created_at', '>=', $since)->count(),
                'avg_response_time' => round(HttpRequest::whereIn('project_id', $allProjectIds)
                    ->where('occurred_at', '>=', $since)->avg('duration_ms') ?? 0, 2),
                'total_requests' => HttpRequest::whereIn('project_id', $allProjectIds)
                    ->where('occurred_at', '>=', $since)->count(),
            ];

            // Exception trend (last 7 days) aggregated across all projects
            $exceptionTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $count = AppException::whereIn('project_id', $allProjectIds)
                    ->whereDate('first_seen_at', $day->toDateString())
                    ->count();
                $exceptionTrend[] = [
                    'date' => $day->format('M d'),
                    'count' => $count,
                ];
            }

            // Request trend (last 7 days) aggregated across all projects
            $requestTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $avg = round(HttpRequest::whereIn('project_id', $allProjectIds)
                    ->whereDate('occurred_at', $day->toDateString())
                    ->avg('duration_ms') ?? 0, 2);
                $requestTrend[] = [
                    'date' => $day->format('M d'),
                    'avg_ms' => $avg,
                ];
            }

            // Top exceptions with most occurrences in the last 24 hours (across all projects)
            $topExceptions = AppException::whereIn('project_id', $allProjectIds)
                ->where('last_seen_at', '>=', $since)
                ->orderBy('occurrence_count', 'desc')
                ->limit(5)
                ->get();

            // Recent activity across all projects
            $recentExceptions = AppException::whereIn('project_id', $allProjectIds)
                ->orderBy('last_seen_at', 'desc')->limit(5)->get()
                ->map(fn($e) => ['type' => 'exception', 'time' => $e->last_seen_at, 'data' => $e, 'project_id' => $e->project_id]);

            $recentJobs = QueueJob::whereIn('project_id', $allProjectIds)
                ->orderBy('created_at', 'desc')->limit(5)->get()
                ->map(fn($j) => ['type' => 'job', 'time' => $j->created_at, 'data' => $j, 'project_id' => $j->project_id]);

            $recentTasks = ScheduledTask::whereIn('project_id', $allProjectIds)
                ->orderBy('created_at', 'desc')->limit(5)->get()
                ->map(fn($t) => ['type' => 'task', 'time' => $t->created_at, 'data' => $t, 'project_id' => $t->project_id]);

            $recentActivity = $recentExceptions->concat($recentJobs)->concat($recentTasks)
                ->sortByDesc('time')->take(15);

            return view('dashboard', [
                'projects' => $projects,
                'project' => null,
                'stats' => $stats,
                'recentActivity' => $recentActivity,
                'exceptionTrend' => $exceptionTrend,
                'requestTrend' => $requestTrend,
                'isSuperAdmin' => $isSuperAdmin,
                'viewMode' => 'all',
                'allProjects' => $projects,
                'topExceptions' => $topExceptions,
                'uptimeStatus' => null,
                'serverMetrics' => collect(),
                'sslStatus' => null,
            ]);
        }

        // --- Single project view (regular user, or super admin viewing a specific project) ---
        if (!$projectId) {
            $project = $projects->first();
            session(['current_project_id' => $project->id]);
        } else {
            $project = Project::find($projectId);
            if (!$project) {
                $project = $projects->first();
                session(['current_project_id' => $project->id]);
            }
        }

        // Stats for last 24 hours
        $since = now()->subDay();

        $stats = [
            'total_exceptions' => AppException::where('project_id', $project->id)->count(),
            'new_exceptions_today' => AppException::where('project_id', $project->id)
                ->where('first_seen_at', '>=', $since)->count(),
            'unresolved_exceptions' => AppException::where('project_id', $project->id)
                ->where('status', 'unresolved')->count(),
            'critical_exceptions' => AppException::where('project_id', $project->id)
                ->where('severity', 'critical')->count(),
            'log_volume' => LogEntry::where('project_id', $project->id)
                ->where('occurred_at', '>=', $since)->count(),
            'queue_failures' => QueueJob::where('project_id', $project->id)
                ->where('status', 'failed')->where('created_at', '>=', $since)->count(),
            'avg_response_time' => round(HttpRequest::where('project_id', $project->id)
                ->where('occurred_at', '>=', $since)->avg('duration_ms') ?? 0, 2),
            'total_requests' => HttpRequest::where('project_id', $project->id)
                ->where('occurred_at', '>=', $since)->count(),
        ];

        // Exception trend (last 7 days)
        $exceptionTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $count = AppException::where('project_id', $project->id)
                ->whereDate('first_seen_at', $day->toDateString())
                ->count();
            $exceptionTrend[] = [
                'date' => $day->format('M d'),
                'count' => $count,
            ];
        }

        // Request trend (last 7 days)
        $requestTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $avg = round(HttpRequest::where('project_id', $project->id)
                ->whereDate('occurred_at', $day->toDateString())
                ->avg('duration_ms') ?? 0, 2);
            $requestTrend[] = [
                'date' => $day->format('M d'),
                'avg_ms' => $avg,
            ];
        }

        // Top exceptions with most occurrences in the last 24 hours (single project)
        $topExceptions = AppException::where('project_id', $project->id)
            ->where('last_seen_at', '>=', $since)
            ->orderBy('occurrence_count', 'desc')
            ->limit(5)
            ->get();

        // Recent activity feed
        $recentExceptions = AppException::where('project_id', $project->id)
            ->orderBy('last_seen_at', 'desc')->limit(5)->get()
            ->map(fn($e) => ['type' => 'exception', 'time' => $e->last_seen_at, 'data' => $e, 'project_id' => $project->id]);

        $recentJobs = QueueJob::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')->limit(5)->get()
            ->map(fn($j) => ['type' => 'job', 'time' => $j->created_at, 'data' => $j, 'project_id' => $project->id]);

        $recentTasks = ScheduledTask::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')->limit(5)->get()
            ->map(fn($t) => ['type' => 'task', 'time' => $t->created_at, 'data' => $t, 'project_id' => $project->id]);

        $recentActivity = $recentExceptions->concat($recentJobs)->concat($recentTasks)
            ->sortByDesc('time')->take(15);

        // Subscription summary
        $uptimeStatus = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'uptime')
            ->where('metric_name', 'is_up')
            ->orderBy('recorded_at', 'desc')
            ->first();

        $serverMetrics = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'server_monitor')
            ->orderBy('recorded_at', 'desc')
            ->limit(4)
            ->get();

        $sslStatus = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'ssl_check')
            ->where('metric_name', 'expiry_days')
            ->orderBy('recorded_at', 'desc')
            ->first();

        // MySQL health metrics
        $mysqlHealth = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'mysql_health')
            ->orderBy('recorded_at', 'desc')
            ->limit(4)
            ->get();

        // Database backup metrics
        $backupMetrics = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'database_backup')
            ->orderBy('recorded_at', 'desc')
            ->limit(2)
            ->get();

        // Domain expiry
        $domainExpiry = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'domain_expiry')
            ->where('metric_name', 'days_until_expiry')
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Service vitals
        $serviceVitals = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'service_vitals')
            ->orderBy('recorded_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'projects' => $projects,
            'project' => $project,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'exceptionTrend' => $exceptionTrend,
            'requestTrend' => $requestTrend,
            'isSuperAdmin' => $isSuperAdmin,
            'viewMode' => 'single',
            'allProjects' => $isSuperAdmin ? $projects : collect(),
            'topExceptions' => $topExceptions,
            'uptimeStatus' => $uptimeStatus,
            'serverMetrics' => $serverMetrics,
            'sslStatus' => $sslStatus,
            'mysqlHealth' => $mysqlHealth,
            'backupMetrics' => $backupMetrics,
            'domainExpiry' => $domainExpiry,
            'serviceVitals' => $serviceVitals,
        ]);
    }
}
