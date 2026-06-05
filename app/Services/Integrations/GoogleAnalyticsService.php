<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class GoogleAnalyticsService
{
    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['google_analytics'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        // Skeleton ready for real GA4 Data API integration
        // Requires service account credentials from integrations_config
        Log::info("GA4: Fetching metrics for project {$project->name}");

        $now = now();

        $metrics = [
            ['metric_name' => 'page_views',      'metric_value' => 0, 'unit' => 'count',   'dimensions' => ['page' => '/']],
            ['metric_name' => 'active_users',    'metric_value' => 0, 'unit' => 'count',   'dimensions' => ['page' => '/']],
            ['metric_name' => 'sessions',        'metric_value' => 0, 'unit' => 'count',   'dimensions' => ['page' => '/']],
            ['metric_name' => 'bounce_rate',     'metric_value' => 0, 'unit' => 'percent', 'dimensions' => ['page' => '/']],
            ['metric_name' => 'avg_session_duration', 'metric_value' => 0, 'unit' => 'seconds', 'dimensions' => []],
        ];

        foreach ($metrics as $meta) {
            IntegrationMetric::create([
                'project_id'   => $project->id,
                'integration'  => 'google_analytics',
                'metric_name'  => $meta['metric_name'],
                'metric_value' => $meta['metric_value'],
                'unit'         => $meta['unit'],
                'dimensions'   => $meta['dimensions'],
                'recorded_at'  => $now,
            ]);
        }
    }
}