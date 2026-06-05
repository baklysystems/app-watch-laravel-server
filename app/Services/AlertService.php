<?php

namespace App\Services;

use App\Jobs\SendAlertNotification;
use App\Models\Alert;
use App\Models\AppException;
use App\Models\IntegrationMetric;
use App\Models\Metric;
use App\Models\QueueJob;
use Carbon\Carbon;

class AlertService
{
    /**
     * Evaluate all active alert rules and trigger notifications if conditions are met.
     */
    public function evaluateAll(): array
    {
        $triggered = [];

        $alerts = Alert::where('is_active', true)
            ->with('project')
            ->get();

        foreach ($alerts as $alert) {
            $result = $this->evaluate($alert);
            if ($result['triggered']) {
                $triggered[] = $result;
            }
        }

        return $triggered;
    }

    /**
     * Evaluate a single alert rule.
     */
    public function evaluate(Alert $alert): array
    {
        // Check cooldown
        if ($alert->last_triggered_at && $alert->cooldown_minutes > 0) {
            $cooldownUntil = Carbon::parse($alert->last_triggered_at)->addMinutes($alert->cooldown_minutes);
            if (now()->lt($cooldownUntil)) {
                return ['triggered' => false, 'alert' => $alert->name, 'reason' => 'cooldown'];
            }
        }

        $triggered = false;
        $details = [];
        $conditions = $alert->conditions ?? [];

        $triggered = match ($alert->type) {
            'exception_rate' => $this->evaluateExceptionRate($alert, $conditions, $details),
            'log_level' => $this->evaluateLogLevel($alert, $conditions, $details),
            'queue_failure' => $this->evaluateQueueFailure($alert, $conditions, $details),
            'query_slow' => $this->evaluateQuerySlow($alert, $conditions, $details),
            'metric_threshold' => $this->evaluateMetricThreshold($alert, $conditions, $details),
            'mysql_connection_saturation' => $this->evaluateMySqlConnectionSaturation($alert, $conditions, $details),
            'mysql_replication_lag' => $this->evaluateMySqlReplicationLag($alert, $conditions, $details),
            'backup_stale' => $this->evaluateBackupStale($alert, $conditions, $details),
            default => false,
        };

        if ($triggered) {
            $this->triggerAlert($alert, $details);
        }

        return [
            'triggered' => $triggered,
            'alert' => $alert->name,
            'type' => $alert->type,
            'details' => $details,
        ];
    }

    protected function evaluateExceptionRate(Alert $alert, array $conditions, array &$details): bool
    {
        $threshold = $conditions['max_count'] ?? 10;
        $windowMinutes = $conditions['window_minutes'] ?? 5;

        $count = AppException::where('project_id', $alert->project_id)
            ->where('first_seen_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        $details['count'] = $count;
        return $count > $threshold;
    }

    protected function evaluateLogLevel(Alert $alert, array $conditions, array &$details): bool
    {
        $levels = $conditions['levels'] ?? ['critical', 'emergency'];
        $windowMinutes = $conditions['window_minutes'] ?? 5;

        $count = \App\Models\LogEntry::where('project_id', $alert->project_id)
            ->whereIn('level', $levels)
            ->where('occurred_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        $details['count'] = $count;
        return $count > 0;
    }

    protected function evaluateQueueFailure(Alert $alert, array $conditions, array &$details): bool
    {
        $threshold = $conditions['max_count'] ?? 1;
        $windowMinutes = $conditions['window_minutes'] ?? 5;

        $count = QueueJob::where('project_id', $alert->project_id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        $details['count'] = $count;
        return $count > $threshold;
    }

    protected function evaluateQuerySlow(Alert $alert, array $conditions, array &$details): bool
    {
        $maxDurationMs = $conditions['max_duration_ms'] ?? 500;
        $windowMinutes = $conditions['window_minutes'] ?? 5;

        $count = \App\Models\DatabaseQuery::where('project_id', $alert->project_id)
            ->where('duration_ms', '>', $maxDurationMs)
            ->where('occurred_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        $details['count'] = $count;
        return $count > 0;
    }

    protected function evaluateMetricThreshold(Alert $alert, array $conditions, array &$details): bool
    {
        $metricName = $conditions['metric_name'] ?? null;
        $operator = $conditions['operator'] ?? '>';
        $threshold = $conditions['value'] ?? 0;

        if (!$metricName) {
            return false;
        }

        $latest = Metric::where('project_id', $alert->project_id)
            ->where('name', $metricName)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$latest) {
            $latest = IntegrationMetric::where('project_id', $alert->project_id)
                ->where('metric_name', $metricName)
                ->orderBy('recorded_at', 'desc')
                ->first();
        }

        if (!$latest) {
            return false;
        }

        $value = $latest->value;
        $details['current_value'] = $value;
        $details['threshold'] = $threshold;

        return match ($operator) {
            '>' => $value > $threshold,
            '<' => $value < $threshold,
            '>=' => $value >= $threshold,
            '<=' => $value <= $threshold,
            '==' => $value == $threshold,
            default => false,
        };
    }

    protected function evaluateMySqlConnectionSaturation(Alert $alert, array $conditions, array &$details): bool
    {
        $thresholdPct = $conditions['max_pct'] ?? 80;

        $latest = IntegrationMetric::where('project_id', $alert->project_id)
            ->where('integration', 'mysql_health')
            ->where('metric_name', 'mysql_connections_saturation_pct')
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$latest) {
            return false;
        }

        $details['current_pct'] = $latest->value;
        return $latest->value > $thresholdPct;
    }

    protected function evaluateMySqlReplicationLag(Alert $alert, array $conditions, array &$details): bool
    {
        $maxLag = $conditions['max_lag_seconds'] ?? 10;

        $latest = IntegrationMetric::where('project_id', $alert->project_id)
            ->where('integration', 'mysql_health')
            ->where('metric_name', 'mysql_replication_lag_seconds')
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$latest) {
            return false;
        }

        $details['current_lag'] = $latest->value;
        return $latest->value > $maxLag;
    }

    protected function evaluateBackupStale(Alert $alert, array $conditions, array &$details): bool
    {
        $maxDays = $conditions['max_days_since_backup'] ?? 2;

        $latest = IntegrationMetric::where('project_id', $alert->project_id)
            ->where('integration', 'db_backup')
            ->where('metric_name', 'backup_success')
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$latest) {
            return false;
        }

        $daysSince = now()->diffInDays($latest->recorded_at);
        $details['days_since_backup'] = $daysSince;
        return $daysSince > $maxDays;
    }

    /**
     * Trigger the alert and dispatch notifications.
     */
    protected function triggerAlert(Alert $alert, array $details): void
    {
        $alert->update(['last_triggered_at' => now()]);

        $channels = $alert->channels ?? [];

        foreach (['mail', 'slack', 'discord', 'webhook'] as $channel) {
            if (in_array($channel, $channels)) {
                dispatch(new SendAlertNotification($alert, $details, $channel));
            }
        }
    }
}