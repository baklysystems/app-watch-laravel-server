<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GitLabService
{
    protected Client $http;
    protected ?string $hostUrl;
    protected string $projectId;
    protected string $accessToken;

    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['gitlab'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        $this->hostUrl = rtrim($config['host_url'] ?? 'https://gitlab.com', '/');
        $this->projectId = urlencode($config['project_id'] ?? '');
        $this->accessToken = $config['access_token'] ?? '';

        if (!$this->projectId || !$this->accessToken) {
            Log::warning("GitLab: Missing project_id or access_token for project {$project->name}");
            return;
        }

        $this->http = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Accept' => 'application/json',
            ],
        ]);

        Log::info("GitLab: Fetching metrics for project {$project->name}");

        $now = now();

        try {
            $this->fetchDeployments($project, $now);
            $this->fetchPipelines($project, $now);
            Log::info("GitLab: Stored metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("GitLab: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }

    protected function fetchDeployments(Project $project, $now): void
    {
        $response = $this->http->get("{$this->hostUrl}/api/v4/projects/{$this->projectId}/deployments", [
            'query' => ['per_page' => 10, 'order_by' => 'created_at', 'sort' => 'desc'],
        ]);

        $deployments = json_decode($response->getBody(), true) ?: [];

        foreach ($deployments as $dep) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'gitlab',
                'metric_name' => 'deployment',
                'metric_value' => 1,
                'unit' => 'count',
                'dimensions' => [
                    'deployment_id' => $dep['id'],
                    'ref' => $dep['ref'],
                    'environment' => $dep['environment']['name'] ?? 'unknown',
                    'status' => $dep['status'],
                    'user' => $dep['user']['username'] ?? 'unknown',
                    'created_at' => $dep['created_at'],
                ],
                'recorded_at' => $now,
            ]);
        }
    }

    protected function fetchPipelines(Project $project, $now): void
    {
        $response = $this->http->get("{$this->hostUrl}/api/v4/projects/{$this->projectId}/pipelines", [
            'query' => ['per_page' => 10, 'order_by' => 'updated_at', 'sort' => 'desc'],
        ]);

        $pipelines = json_decode($response->getBody(), true) ?: [];

        foreach ($pipelines as $pipe) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'gitlab',
                'metric_name' => 'pipeline',
                'metric_value' => 1,
                'unit' => 'count',
                'dimensions' => [
                    'pipeline_id' => $pipe['id'],
                    'ref' => $pipe['ref'],
                    'status' => $pipe['status'],
                    'source' => $pipe['source'],
                    'user' => $pipe['user']['username'] ?? 'unknown',
                    'duration' => $pipe['duration'] ?? 0,
                    'created_at' => $pipe['created_at'],
                ],
                'recorded_at' => $now,
            ]);
        }
    }

    /**
     * Handle incoming GitLab webhook event.
     */
    public function handleWebhook(Project $project, string $eventType, array $payload): void
    {
        $now = now();
        $dimensions = [];
        $metricName = '';

        switch ($eventType) {
            case 'Deployment Hook':
                $metricName = 'deployment';
                $dimensions = [
                    'deployment_id' => $payload['deployment_id'],
                    'status' => $payload['status'],
                    'environment' => $payload['environment'] ?? 'unknown',
                ];
                break;
            case 'Pipeline Hook':
                $metricName = 'pipeline';
                $pipe = $payload['object_attributes'] ?? $payload;
                $dimensions = [
                    'pipeline_id' => $pipe['id'],
                    'ref' => $pipe['ref'],
                    'status' => $pipe['status'],
                    'duration' => $pipe['duration'] ?? 0,
                    'user' => $payload['user']['username'] ?? 'unknown',
                ];
                break;
            case 'Release Hook':
                $metricName = 'release';
                $dimensions = [
                    'tag' => $payload['tag'],
                    'name' => $payload['name'],
                    'released_at' => $payload['released_at'],
                ];
                break;
            default:
                return;
        }

        if ($metricName) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'gitlab',
                'metric_name' => $metricName,
                'metric_value' => 1,
                'unit' => 'count',
                'dimensions' => $dimensions,
                'recorded_at' => $now,
            ]);
        }
    }
}