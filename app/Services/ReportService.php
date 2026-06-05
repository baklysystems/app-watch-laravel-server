<?php

namespace App\Services;

use App\Models\Exception;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    protected HealthScoreService $healthScore;

    public function __construct()
    {
        $this->healthScore = app(HealthScoreService::class);
    }

    public function generateWeekly(Project $project): string
    {
        $startDate = now()->subWeek()->startOfWeek();
        $endDate = now()->subWeek()->endOfWeek();

        $data = $this->gatherReportData($project, $startDate, $endDate);

        try {
            $pdf = Pdf::loadView('reports.weekly', $data);
            $output = $pdf->output();
            Log::info("Report: Generated weekly PDF for {$project->name}");
            return $output;
        } catch (\Throwable $e) {
            Log::warning("Report: PDF generation failed for {$project->name} — {$e->getMessage()}. Falling back to HTML.");
            return view('reports.weekly', $data)->render();
        }
    }

    public function generateHtml(Project $project): string
    {
        $startDate = now()->subWeek()->startOfWeek();
        $endDate = now()->subWeek()->endOfWeek();

        return view('reports.weekly', $this->gatherReportData($project, $startDate, $endDate))->render();
    }

    protected function gatherReportData(Project $project, $startDate, $endDate): array
    {
        $healthScore = $this->healthScore->calculate($project);

        // Exception summary
        $exceptionSummary = Exception::where('project_id', $project->id)
            ->where('last_seen_at', '>=', $startDate)
            ->where('last_seen_at', '<=', $endDate)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'unresolved' THEN 1 ELSE 0 END) as new_count,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                SUM(occurrence_count) as total_occurrences
            ")
            ->first();

        // Top 5 exceptions
        $topExceptions = Exception::where('project_id', $project->id)
            ->where('last_seen_at', '>=', $startDate)
            ->where('last_seen_at', '<=', $endDate)
            ->orderBy('occurrence_count', 'desc')
            ->limit(5)
            ->get(['class', 'occurrence_count', 'message']);

        // Uptime
        $uptimeMetric = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'uptime')
            ->where('metric_name', 'uptime_pct')
            ->where('recorded_at', '>=', $startDate)
            ->where('recorded_at', '<=', $endDate)
            ->orderBy('recorded_at', 'desc')
            ->first();

        $uptimePct = $uptimeMetric ? (float) $uptimeMetric->metric_value : 100;

        // Avg response time
        $avgResponseTime = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'uptime')
            ->where('metric_name', 'response_time_ms')
            ->where('recorded_at', '>=', $startDate)
            ->where('recorded_at', '<=', $endDate)
            ->avg('metric_value') ?? 0;

        // Queue health
        $queueStats = DB::table('queued_jobs')
            ->where('project_id', $project->id)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN duration IS NOT NULL THEN duration END) as avg_duration
            ")
            ->first();

        // Revenue (Stripe)
        $revenueMetrics = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'stripe')
            ->where('metric_name', 'charge_succeeded')
            ->where('recorded_at', '>=', $startDate)
            ->where('recorded_at', '<=', $endDate)
            ->sum('metric_value');

        // Traffic (GA4)
        $ga4Metrics = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'google_analytics')
            ->where('recorded_at', '>=', $startDate)
            ->where('recorded_at', '<=', $endDate)
            ->get()
            ->groupBy('metric_name');

        $pageViews = 0;
        $activeUsers = 0;
        if (isset($ga4Metrics['page_views'])) {
            $pageViews = $ga4Metrics['page_views']->sum('metric_value');
        }
        if (isset($ga4Metrics['active_users'])) {
            $activeUsers = $ga4Metrics['active_users']->sum('metric_value');
        }

        return compact(
            'project', 'startDate', 'endDate', 'healthScore',
            'exceptionSummary', 'topExceptions', 'uptimePct',
            'avgResponseTime', 'queueStats', 'revenueMetrics',
            'pageViews', 'activeUsers'
        );
    }
}