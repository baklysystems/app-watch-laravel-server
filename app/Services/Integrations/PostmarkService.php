<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PostmarkService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 30, 'connect_timeout' => 10]);
    }

    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['mail_provider'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        $serverToken = $config['server_token'] ?? null;

        if (!$serverToken) {
            Log::warning("Postmark: Missing server_token for project {$project->name}");
            return;
        }

        Log::info("Postmark: Fetching metrics for project {$project->name}");

        $now = now();

        try {
            $response = $this->http->get('https://api.postmarkapp.com/stats/outbound/sends', [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Postmark-Server-Token' => $serverToken,
                ],
                'query' => ['count' => 1, 'offset' => 0],
            ]);

            $data = json_decode($response->getBody(), true);
            $today = $data['Days'][0] ?? [];

            $sent = $today['Sent'] ?? 0;
            $bounced = $today['Bounced'] ?? 0;
            $delivered = $sent - $bounced;
            $opened = $today['Opened'] ?? 0;
            $clicked = $today['Clicked'] ?? 0;
            $complaints = $today['SpamComplaint'] ?? 0;
            $deliveryRate = $sent > 0 ? round(($delivered / $sent) * 100, 2) : 100;

            $metrics = [
                ['name' => 'sent', 'value' => $sent, 'unit' => 'count'],
                ['name' => 'delivered', 'value' => $delivered, 'unit' => 'count'],
                ['name' => 'bounced', 'value' => $bounced, 'unit' => 'count'],
                ['name' => 'opened', 'value' => $opened, 'unit' => 'count'],
                ['name' => 'clicked', 'value' => $clicked, 'unit' => 'count'],
                ['name' => 'complaints', 'value' => $complaints, 'unit' => 'count'],
                ['name' => 'delivery_rate', 'value' => $deliveryRate, 'unit' => 'percent'],
            ];

            foreach ($metrics as $metric) {
                IntegrationMetric::create([
                    'project_id' => $project->id,
                    'integration' => 'postmark',
                    'metric_name' => $metric['name'],
                    'metric_value' => $metric['value'],
                    'unit' => $metric['unit'],
                    'dimensions' => [],
                    'recorded_at' => $now,
                ]);
            }

            Log::info("Postmark: Stored metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("Postmark: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }
}