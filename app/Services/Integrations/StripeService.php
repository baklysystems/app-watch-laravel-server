<?php
namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['stripe'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        Log::info("Stripe: Fetching metrics for project {$project->name}");
        $now = now();

        $metrics = [
            ['metric_name' => 'mrr',                  'metric_value' => 0, 'unit' => 'usd'],
            ['metric_name' => 'successful_charges',    'metric_value' => 0, 'unit' => 'count'],
            ['metric_name' => 'failed_charges',        'metric_value' => 0, 'unit' => 'count'],
            ['metric_name' => 'refunds',               'metric_value' => 0, 'unit' => 'count'],
            ['metric_name' => 'active_subscriptions',  'metric_value' => 0, 'unit' => 'count'],
            ['metric_name' => 'disputes_open',         'metric_value' => 0, 'unit' => 'count'],
        ];

        foreach ($metrics as $meta) {
            IntegrationMetric::create([
                'project_id'   => $project->id,
                'integration'  => 'stripe',
                'metric_name'  => $meta['metric_name'],
                'metric_value' => $meta['metric_value'],
                'unit'         => $meta['unit'],
                'dimensions'   => [],
                'recorded_at'  => $now,
            ]);
        }
    }
}