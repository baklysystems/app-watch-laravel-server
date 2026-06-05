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
    /**
     * Cache TTL for dashboard stats (2 minutes — frequent enough to stay fresh, 
     * long enough to prevent query storms on high-traffic dashboards).
     */
    private const CACHE_TTL = 120;

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $projectId = $request->input('project_id', session('current_project_id'));

        // Super admins see all active projects; regular users see their own projects
        $cacheKey = 'dashboard:projects:' . ($isSuperAdmin ? 'all' : $user->id);
        $projects = cache()->remember($cacheKey, self::CACHE_TTL, function () use ($isSuperAdmin, $user) {
            if ($isSuperAdmin) {
                return Project::where('is_active', true)->get();
            }
            return Project::where('is_active', true)
                ->where('user_id', $user->id)
                ->get();
        });

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
            return $this->allProjectsView($projects, $isSuperAdmin);
        }

        return $this->singleProjectView($projects, $projectId, $isSuperAdmin);
    }

    /**
     * Super admin aggregated view across all projects.
     */
    private function allProjectsView($projects, bool $isSuperAdmin)
    {
        $allProjectIds = $projects->pluck('id')->toArray();
        $cacheKey = 'dashboard:all:' . md5(implode(',', $allProjectIds));
        $since = now()->subDay();

        $stats = cache()->remember($cacheKey . ':stats', self::CACHE_TTL, function () use ($allProjectIds, $since) {
            return $this->computeStats($allProjectIds, $since);
        });

        $exceptionTrend = cache()->remember($cacheKey . ':exceptionTrend', self::CACHE_TTL, function () use ($allProjectIds) {
            return $this->computeExceptionTrend($allProjectIds);
        });

        $requestTrend = cache()->remember($cacheKey . ':requestTrend', self::CACHE_TTL, function () use ($allProjectIds) {
            return $this->computeRequestTrend($allProjectIds);
        });

        // Top exceptions — cache separately since they change frequently
        $topExceptions = AppException::whereIn('project_id', $allProjectIds)
            ->where('last_seen_at', '>=', $since)
            ->orderBy('occurrence_count', 'desc')
            ->limit(5)
            ->get();

        // Recent activity across all projects — cached
        $recentActivity = cache()->remember($cacheKey . ':recentActivity', 60, function () use ($allProjectIds) {
            $recentExceptions = AppException::whereIn('project_id', $allProjectIds)
                ->orderBy('last_seen_at', 'desc')->limit(5)->get()
                ->map(fn($e) => ['type' => 'exception', 'time' => $e->last_seen_at, 'data' => $e, 'project_id' => $e->project_id]);

            $recentJobs = QueueJob::whereIn('project_id', $allProjectIds)
                ->orderBy('created_at', 'desc')->limit(5)->get()
                ->map(fn($j) => ['type' => 'job', 'time' => $j->created_at, 'data' => $j, 'project_id' => $j->project_id]);

            $recentTasks = ScheduledTask::whereIn('project_id', $allProjectIds)
                ->orderBy('created_at', 'desc')->limit(5)->get()
                ->map(fn($t) => ['type' => 'task', 'time' => $t->created_at, 'data' => $t, 'project_id' => $t->project_id]);

            return $recentExceptions->concat($recentJobs)->concat($recentTasks)
                ->sortByDesc('time')->take(15)->values();
        });

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

    /**
     * Single project dashboard view.
     */
    private function singleProjectView($projects, $projectId, bool $isSuperAdmin)
    {
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

        $cacheKey = 'dashboard:project:' . $project->id;
        $since = now()->subDay();

        // Cache the stats + trends together
        $cachedData = cache()->remember($cacheKey, self::CACHE_TTL, function () use ($project, $since, $cacheKey) {
            $stats = $this->computeStats([$project->id], $since);
            $exceptionTrend = $this->computeExceptionTrend([$project->id]);
            $requestTrend = $this->computeRequestTrend([$project->id]);

            return compact('stats', 'exceptionTrend', 'requestTrend');
        });

        // Top exceptions — lightweight query, short cache
        $topExceptions = AppException::where('project_id', $project->id)
            ->where('last_seen_at', '>=', $since)
            ->orderBy('occurrence_count', 'desc')
            ->limit(5)
            ->get();

        // Recent activity feed — cached separately, shorter TTL
        $recentActivity = cache()->remember($cacheKey . ':recentActivity', 60, function () use ($project) {
            $recentExceptions = AppException::where('project_id', $project->id)
                ->orderBy('last_seen_at', 'desc')->limit(5)->get()
                ->map(fn($e) => ['type' => 'exception', 'time' => $e->last_seen_at, 'data' => $e, 'project_id' => $project->id]);

            $recentJobs = QueueJob::where('project_id', $project->id)
                ->orderBy('created_at', 'desc')->limit(5)->get()
                ->map(fn($j) => ['type' => 'job', 'time' => $j->created_at, 'data' => $j, 'project_id' => $project->id]);

            $recentTasks = ScheduledTask::where('project_id', $project->id)
                ->orderBy('created_at', 'desc')->limit(5)->get()
                ->map(fn($t) => ['type' => 'task', 'time' => $t->created_at, 'data' => $t, 'project_id' => $project->id]);

            return $recentExceptions->concat($recentJobs)->concat($recentTasks)
                ->sortByDesc('time')->take(15)->values();
        });

        // Integration metrics — cache all together in one key
        $integrationData = cache()->remember($cacheKey . ':integrations', self::CACHE_TTL, function () use ($project) {
            return [
                'uptimeStatus' => IntegrationMetric::where('project_id', $project->id)
                    ->where('integration', 'uptime')
                    ->where('metric_name', 'is_up')
                    ->orderBy('recorded_at', 'desc')
                    ->first(),
                'serverMetrics' => IntegrationMetric::where('project_id', $project->id)
                    ->where('integration', 'server_monitor')
                    ->orderBy('recorded_at', 'desc')
                    ->limit(4)
                    ->get(),
                'sslStatus' => IntegrationMetric::where('project_id', $project->id)
                    ->where('integration', 'ssl_check')
                    ->where('metric_name', 'expiry_days')
                    ->orderBy('recorded_at', 'desc')
                    ->first(),
                'mysqlHealth' => IntegrationMetric::where('project_id', $project->id)
                    ->where('integration', 'mysql_health')
                    ->orderBy('recorded_at', 'desc')
                    ->limit(4)
                    ->get(),
                'backupMetrics' => IntegrationMetric::where('project_id', $project->id)
                    ->where('integration', 'database_backup')
                    ->orderBy('recorded_at', 'desc')
                    ->limit(2)
                    ->get(),
                'domainExpiry' => IntegrationMetric::where('project_id', $project->id)
                    ->where('integration', 'domain_expiry')
                    ->where('metric_name', 'days_until_expiry')
                    ->orderBy('recorded_at', 'desc')
                    ->first(),
                'serviceVitals' => IntegrationMetric::where('project_id', $project->id)
                    ->where('integration', 'service_vitals')
                    ->orderBy('recorded_at', 'desc')
                    ->limit(5)
                    ->get(),
            ];
        });

        return view('dashboard', [
            'projects' => $projects,
            'project' => $project,
            'stats' => $cachedData['stats'],
            'recentActivity' => $recentActivity,
            'exceptionTrend' => $cachedData['exceptionTrend'],
            'requestTrend' => $cachedData['requestTrend'],
            'isSuperAdmin' => $isSuperAdmin,
            'viewMode' => 'single',
            'allProjects' => $isSuperAdmin ? $projects : collect(),
            'topExceptions' => $topExceptions,
            'uptimeStatus' => $integrationData['uptimeStatus'],
            'serverMetrics' => $integrationData['serverMetrics'],
            'sslStatus' => $integrationData['sslStatus'],
            'mysqlHealth' => $integrationData['mysqlHealth'],
            'backupMetrics' => $integrationData['backupMetrics'],
            'domainExpiry' => $integrationData['domainExpiry'],
            'serviceVitals' => $integrationData['serviceVitals'],
        ]);
    }

    /**
     * Compute dashboard stats for given project IDs.
     */
    private function computeStats(array $projectIds, $since): array
    {
        return [
            'total_exceptions' => AppException::whereIn('project_id', $projectIds)->count(),
            'new_exceptions_today' => AppException::whereIn('project_id', $projectIds)
                ->where('first_seen_at', '>=', $since)->count(),
            'unresolved_exceptions' => AppException::whereIn('project_id', $projectIds)
                ->where('status', 'unresolved')->count(),
            'critical_exceptions' => AppException::whereIn('project_id', $projectIds)
                ->where('severity', 'critical')->count(),
            'log_volume' => LogEntry::whereIn('project_id', $projectIds)
                ->where('occurred_at', '>=', $since)->count(),
            'queue_failures' => QueueJob::whereIn('project_id', $projectIds)
                ->where('status', 'failed')->where('created_at', '>=', $since)->count(),
            'avg_response_time' => round(HttpRequest::whereIn('project_id', $projectIds)
                ->where('occurred_at', '>=', $since)->avg('duration_ms') ?? 0, 2),
            'total_requests' => HttpRequest::whereIn('project_id', $projectIds)
                ->where('occurred_at', '>=', $since)->count(),
        ];
    }

    /**
     * Compute exception trend (last 7 days) — single query with GROUP BY.
     */
    private function computeExceptionTrend(array $projectIds): array
    {
        $rows = AppException::whereIn('project_id', $projectIds)
            ->where('first_seen_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(first_seen_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $trend[] = [
                'date' => now()->subDays($i)->format('M d'),
                'count' => (int) ($rows->get($day)->count ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * Compute request trend (last 7 days) — single query with GROUP BY.
     */
    private function computeRequestTrend(array $projectIds): array
    {
        $rows = HttpRequest::whereIn('project_id', $projectIds)
            ->where('occurred_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(occurred_at) as date, AVG(duration_ms) as avg_ms')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $trend[] = [
                'date' => now()->subDays($i)->format('M d'),
                'avg_ms' => round((float) ($rows->get($day)->avg_ms ?? 0), 2),
            ];
        }

        return $trend;
    }
}
