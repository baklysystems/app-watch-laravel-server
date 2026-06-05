<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnomalyDetectionService
{
    /**
     * Detect anomalies using Z-score with configurable threshold.
     */
    public function detectAnomalies(array $values, float $threshold = 2.5): array
    {
        $dataPoints = array_values($values);
        $count = count($dataPoints);

        if ($count < 10) {
            return [];
        }

        $historical = array_slice($dataPoints, 0, -1);
        $latest = end($dataPoints);
        $latestTimestamp = array_key_last($values);

        $mean = array_sum($historical) / count($historical);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $historical)) / count($historical);
        $stdDev = sqrt($variance);

        if ($stdDev == 0) {
            return [];
        }

        $zScore = abs(($latest - $mean) / $stdDev);

        if ($zScore >= $threshold) {
            return [[
                'timestamp' => $latestTimestamp,
                'value' => $latest,
                'mean' => round($mean, 2),
                'stddev' => round($stdDev, 2),
                'z_score' => round($zScore, 2),
                'direction' => $latest > $mean ? 'spike' : 'drop',
                'deviation_pct' => round((($latest - $mean) / $mean) * 100, 1),
            ]];
        }

        return [];
    }

    public function checkExceptionRate(Project $project): array
    {
        $buckets = DB::table('exceptions')
            ->where('project_id', $project->id)
            ->where('last_seen_at', '>=', now()->subHours(24))
            ->selectRaw("DATE_FORMAT(last_seen_at, '%Y-%m-%d %H:00') as bucket, COUNT(*) as count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('count', 'bucket')
            ->toArray();

        $anomalies = $this->detectAnomalies($buckets, 2.5);

        if (!empty($anomalies)) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'anomaly_detection',
                'metric_name' => 'exception_rate_anomaly',
                'metric_value' => $anomalies[0]['z_score'],
                'unit' => 'z_score',
                'dimensions' => json_encode($anomalies[0]),
                'recorded_at' => now(),
            ]);

            $this->triggerAnomalyAlert($project, 'exception_rate', $anomalies[0]);
        }

        return $anomalies;
    }

    public function checkResponseTime(Project $project): array
    {
        $buckets = DB::table('http_requests')
            ->where('project_id', $project->id)
            ->where('occurred_at', '>=', now()->subHours(24))
            ->selectRaw("DATE_FORMAT(occurred_at, '%Y-%m-%d %H:00') as bucket, AVG(duration_ms) as avg_duration")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('avg_duration', 'bucket')
            ->toArray();

        $anomalies = $this->detectAnomalies($buckets, 2.5);

        if (!empty($anomalies)) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'anomaly_detection',
                'metric_name' => 'response_time_anomaly',
                'metric_value' => $anomalies[0]['z_score'],
                'unit' => 'z_score',
                'dimensions' => json_encode($anomalies[0]),
                'recorded_at' => now(),
            ]);

            $this->triggerAnomalyAlert($project, 'response_time', $anomalies[0]);
        }

        return $anomalies;
    }

    public function checkQueueFailures(Project $project): array
    {
        $buckets = DB::table('queue_jobs')
            ->where('project_id', $project->id)
            ->where('status', 'failed')
            ->where('finished_at', '>=', now()->subHours(24))
            ->selectRaw("DATE_FORMAT(finished_at, '%Y-%m-%d %H:00') as bucket, COUNT(*) as count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('count', 'bucket')
            ->toArray();

        $anomalies = $this->detectAnomalies($buckets, 2.5);

        if (!empty($anomalies)) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'anomaly_detection',
                'metric_name' => 'queue_failure_anomaly',
                'metric_value' => $anomalies[0]['z_score'],
                'unit' => 'z_score',
                'dimensions' => json_encode($anomalies[0]),
                'recorded_at' => now(),
            ]);

            $this->triggerAnomalyAlert($project, 'queue_failure', $anomalies[0]);
        }

        return $anomalies;
    }

    /**
     * Run all anomaly checks for all active projects.
     */
    public function runAllChecks(): array
    {
        $projects = Project::where('is_active', true)->get();
        $summary = [];

        foreach ($projects as $project) {
            $exAnomalies = $this->checkExceptionRate($project);
            $rtAnomalies = $this->checkResponseTime($project);
            $qfAnomalies = $this->checkQueueFailures($project);

            $total = count($exAnomalies) + count($rtAnomalies) + count($qfAnomalies);
            $summary[$project->name] = $total;

            if ($total > 0) {
                Log::info("Anomaly detected for {$project->name}", [
                    'exceptions' => count($exAnomalies),
                    'response_time' => count($rtAnomalies),
                    'queue_failures' => count($qfAnomalies),
                ]);
            }
        }

        return $summary;
    }

    protected function triggerAnomalyAlert(Project $project, string $metric, array $anomaly): void
    {
        try {
            $alert = Alert::where('project_id', $project->id)
                ->where('type', 'anomaly_detected')
                ->where('is_active', true)
                ->first();

            if (!$alert) return;

            $details = [
                'metric' => $metric,
                'current_value' => $anomaly['value'],
                'average' => $anomaly['mean'],
                'z_score' => $anomaly['z_score'],
                'direction' => $anomaly['direction'],
                'deviation_pct' => $anomaly['deviation_pct'] . '%',
            ];

            // Check cooldown
            $cooldownMinutes = $alert->cooldown_minutes ?? 5;
            if ($alert->last_triggered_at && $alert->last_triggered_at->diffInMinutes(now()) < $cooldownMinutes) {
                return;
            }

            foreach ($alert->channels ?? [] as $channel) {
                \App\Jobs\SendAlertNotification::dispatch($alert, $details, $channel);
            }

            $alert->update(['last_triggered_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Failed to trigger anomaly alert: ' . $e->getMessage());
        }
    }
}