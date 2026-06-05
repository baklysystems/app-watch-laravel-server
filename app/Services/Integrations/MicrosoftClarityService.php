<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MicrosoftClarityService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 30, 'connect_timeout' => 10]);
    }

    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['microsoft_clarity'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        $projectId = $config['project_id'] ?? null;
        $apiToken = $config['api_token'] ?? null;

        if (!$projectId || !$apiToken) {
            Log::warning("Clarity: Missing project_id or api_token for project {$project->name}");
            return;
        }

        Log::info("Clarity: Fetching metrics for project {$project->name}");

        $now = now();

        try {
            $metrics = $this->fetchMetrics($projectId, $apiToken);
            foreach ($metrics as $metric) {
                IntegrationMetric::create([
                    'project_id'   => $project->id,
                    'integration'  => 'microsoft_clarity',
                    'metric_name'  => $metric['name'],
                    'metric_value' => $metric['value'],
                    'unit'         => $metric['unit'],
                    'dimensions'   => [],
                    'recorded_at'  => $now,
                ]);
            }
            Log::info("Clarity: Stored " . count($metrics) . " metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("Clarity: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }

    protected function fetchMetrics(string $projectId, string $apiToken): array
    {
        // Clarity API — fetch summary for last 24h
        $response = $this->http->get("https://www.clarity.ms/api/rest/v1/projects/{$projectId}/insights/summary", [
            'headers' => [
                'Authorization' => "Bearer {$apiToken}",
                'Accept'        => 'application/json',
            ],
            'query' => ['days' => 1],
        ]);

        $data = json_decode($response->getBody(), true) ?: [];

        $totalSessions = $data['totalSessions'] ?? 0;
        $rageClicks = $data['rageClicks'] ?? 0;
        $deadClicks = $data['deadClicks'] ?? 0;
        $excessiveScrolls = $data['excessiveScrolls'] ?? 0;
        $jsErrors = $data['jsErrors'] ?? 0;
        $sessionRecordings = $data['sessionRecordings'] ?? 0;

        return [
            ['name' => 'total_sessions', 'value' => $totalSessions, 'unit' => 'count'],
            ['name' => 'rage_clicks', 'value' => $rageClicks, 'unit' => 'count'],
            ['name' => 'dead_clicks', 'value' => $deadClicks, 'unit' => 'count'],
            ['name' => 'excessive_scrolls', 'value' => $excessiveScrolls, 'unit' => 'count'],
            ['name' => 'js_errors', 'value' => $jsErrors, 'unit' => 'count'],
            ['name' => 'session_recordings', 'value' => $sessionRecordings, 'unit' => 'count'],
        ];
    }
}