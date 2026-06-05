<?php

namespace App\Services;

use App\Models\AppException;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthScoreService
{
    /**
     * Calculate composite health score for a project (0-100).
     */
    public function calculate(Project $project): array
    {
        return Cache::remember("health_score:{$project->id}", 300, function () use ($project) {
            $scores = [
                'error_rate'     => round($this->scoreErrorRate($project) * 0.30, 1),
                'uptime'         => round($this->scoreUptime($project) * 0.25, 1),
                'response_time'  => round($this->scoreResponseTime($project) * 0.20, 1),
                'queue_health'   => round($this->scoreQueueHealth($project) * 0.15, 1),
                'recent_alerts'  => round($this->scoreRecentAlerts($project) * 0.10, 1),
            ];

            $total = (int) round(array_sum($scores));
            $grade = $total >= 90 ? 'A' : ($total >= 75 ? 'B' : ($total >= 60 ? 'C' : ($total >= 40 ? 'D' : 'F')));
            $color = $total >= 75 ? 'green' : ($total >= 50 ? 'yellow' : 'red');

            return [
                'total'      => $total,
                'grade'      => $grade,
                'color'      => $color,
                'scores'     => $scores,
                'project_id' => $project->id,
            ];
        });
    }

    protected function scoreErrorRate(Project $project): float
    {
        $today = AppException::where('project_id', $project->id)
            ->where('last_seen_at', '>=', now()->subDay())->count();

        $avg = AppException::where('project_id', $project->id)
            ->whereBetween('last_seen_at', [now()->subDays(7), now()->subDay()])->count() / 6;

        if ($avg == 0 && $today == 0) return 100;
        $ratio = $today / max($avg, 1);
        return max(0, min(100, 100 - ($ratio - 1) * 50));
    }

    protected function scoreUptime(Project $project): float
    {
        $metric = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'uptime')
            ->where('metric_name', 'is_up')
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$metric) return 50;

        $pct = $metric->metric_value == 1 ? 100 : (float) $metric->metric_value;
        if ($pct >= 99.9) return 100;
        if ($pct >= 99.0) return 90;
        if ($pct >= 95.0) return 50;
        return max(0, $pct);
    }

    protected function scoreResponseTime(Project $project): float
    {
        $avg = DB::table('http_requests')
            ->where('project_id', $project->id)
            ->where('occurred_at', '>=', now()->subHour())
            ->avg('duration_ms');

        if (!$avg) return 100;
        if ($avg < 100) return 100;
        if ($avg < 300) return 85;
        if ($avg < 500) return 60;
        if ($avg < 1000) return 30;
        if ($avg < 2000) return 10;
        return 0;
    }

    protected function scoreQueueHealth(Project $project): float
    {
        $failed = DB::table('queue_jobs')
            ->where('project_id', $project->id)
            ->where('status', 'failed')
            ->where('finished_at', '>=', now()->subDay())
            ->count();

        if ($failed == 0) return 100;
        if ($failed <= 2) return 80;
        if ($failed <= 5) return 50;
        return 0;
    }

    protected function scoreRecentAlerts(Project $project): float
    {
        $count = DB::table('alerts')
            ->where('project_id', $project->id)
            ->whereNotNull('last_triggered_at')
            ->where('last_triggered_at', '>=', now()->subDay())
            ->count();

        if ($count == 0) return 100;
        if ($count == 1) return 80;
        if ($count <= 3) return 50;
        return 0;
    }
}