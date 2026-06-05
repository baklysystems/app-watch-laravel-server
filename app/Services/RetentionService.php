<?php

namespace App\Services;

use App\Models\AppException;
use App\Models\DatabaseQuery;
use App\Models\HttpRequest;
use App\Models\LogEntry;
use App\Models\Metric;
use App\Models\Project;
use App\Models\QueueJob;
use App\Models\ScheduledTask;
use Carbon\Carbon;

class RetentionService
{
    /**
     * Execute data retention cleanup across all projects.
     * Deletes records older than each project's configured retention_days.
     */
    public function cleanup(): array
    {
        $results = [];
        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $results[$project->slug] = $this->cleanupProject($project);
        }

        return $results;
    }

    /**
     * Clean up data for a single project based on its retention policy.
     */
    public function cleanupProject(Project $project): array
    {
        $retentionDays = $project->retention_days ?? 30;
        $cutoff = Carbon::now()->subDays($retentionDays);

        $stats = [];

        // Delete resolved exceptions older than retention
        $stats['exceptions_resolved'] = AppException::where('project_id', $project->id)
            ->where('status', 'resolved')
            ->where('last_seen_at', '<', $cutoff)
            ->delete();

        // Delete ignored/muted exceptions (always default to retention)
        $stats['exceptions_ignored'] = AppException::where('project_id', $project->id)
            ->whereIn('status', ['ignored', 'muted'])
            ->where('last_seen_at', '<', $cutoff)
            ->delete();

        // Delete old log entries
        $stats['log_entries'] = LogEntry::where('project_id', $project->id)
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        // Delete old queue job records
        $stats['queue_jobs'] = QueueJob::where('project_id', $project->id)
            ->where('finished_at', '<', $cutoff)
            ->delete();

        // Delete old database query records
        $stats['database_queries'] = DatabaseQuery::where('project_id', $project->id)
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        // Delete old HTTP request records
        $stats['http_requests'] = HttpRequest::where('project_id', $project->id)
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        // Delete old scheduled task records
        $stats['scheduled_tasks'] = ScheduledTask::where('project_id', $project->id)
            ->where('finished_at', '<', $cutoff)
            ->whereNotNull('finished_at')
            ->delete();

        // Delete old metrics
        $stats['metrics'] = Metric::where('project_id', $project->id)
            ->where('recorded_at', '<', $cutoff)
            ->delete();

        return $stats;
    }

    /**
     * Get a summary of how many records will be deleted for a given project
     * without actually deleting them. Useful for admin preview.
     */
    public function preview(Project $project, ?int $retentionDays = null): array
    {
        $retentionDays = $retentionDays ?? $project->retention_days ?? 30;
        $cutoff = Carbon::now()->subDays($retentionDays);

        return [
            'exceptions_resolved' => AppException::where('project_id', $project->id)->where('status', 'resolved')->where('last_seen_at', '<', $cutoff)->count(),
            'exceptions_ignored' => AppException::where('project_id', $project->id)->whereIn('status', ['ignored', 'muted'])->where('last_seen_at', '<', $cutoff)->count(),
            'log_entries' => LogEntry::where('project_id', $project->id)->where('occurred_at', '<', $cutoff)->count(),
            'queue_jobs' => QueueJob::where('project_id', $project->id)->where('finished_at', '<', $cutoff)->count(),
            'database_queries' => DatabaseQuery::where('project_id', $project->id)->where('occurred_at', '<', $cutoff)->count(),
            'http_requests' => HttpRequest::where('project_id', $project->id)->where('occurred_at', '<', $cutoff)->count(),
            'scheduled_tasks' => ScheduledTask::where('project_id', $project->id)->where('finished_at', '<', $cutoff)->whereNotNull('finished_at')->count(),
            'metrics' => Metric::where('project_id', $project->id)->where('recorded_at', '<', $cutoff)->count(),
        ];
    }
}