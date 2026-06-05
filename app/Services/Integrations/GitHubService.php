<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => ['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28'],
        ]);
    }

    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['github'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        $token = $config['personal_access_token'] ?? null;
        $repo = $config['repository'] ?? null;

        if (!$token || !$repo) {
            Log::warning("GitHub: Missing token or repository for project {$project->name}");
            return;
        }

        Log::info("GitHub: Fetching metrics for project {$project->name}");

        $now = now();

        try {
            // Fetch deployments
            $this->fetchDeployments($project, $token, $repo, $now);
            // Fetch workflow runs
            $this->fetchWorkflowRuns($project, $token, $repo, $now);
            // Fetch releases
            $this->fetchReleases($project, $token, $repo, $now);

            Log::info("GitHub: Stored metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("GitHub: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }

    protected function fetchDeployments(Project $project, string $token, string $repo, $now): void
    {
        $response = $this->http->get("https://api.github.com/repos/{$repo}/deployments", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'query' => ['per_page' => 10],
        ]);

        $deployments = json_decode($response->getBody(), true) ?: [];

        foreach ($deployments as $deployment) {
            $statusUrl = $deployment['statuses_url'] ?? null;
            $latestStatus = 'unknown';
            $statusTimestamp = null;

            if ($statusUrl) {
                $statusResponse = $this->http->get($statusUrl, [
                    'headers' => ['Authorization' => "Bearer {$token}"],
                ]);
                $statuses = json_decode($statusResponse->getBody(), true) ?: [];
                if ($statuses) {
                    $latest = $statuses[0];
                    $latestStatus = $latest['state'] ?? 'unknown';
                    $statusTimestamp = $latest['created_at'] ?? null;
                }
            }

            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'github',
                'metric_name' => 'deployment',
                'metric_value' => 1,
                'unit' => 'count',
                'dimensions' => [
                    'deployment_id' => $deployment['id'],
                    'ref' => $deployment['ref'],
                    'environment' => $deployment['environment'],
                    'status' => $latestStatus,
                    'creator' => $deployment['creator']['login'] ?? 'unknown',
                    'status_timestamp' => $statusTimestamp,
                ],
                'recorded_at' => $now,
            ]);
        }
    }

    protected function fetchWorkflowRuns(Project $project, string $token, string $repo, $now): void
    {
        $response = $this->http->get("https://api.github.com/repos/{$repo}/actions/runs", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'query' => ['per_page' => 10],
        ]);

        $runs = json_decode($response->getBody(), true);
        $workflowRuns = $runs['workflow_runs'] ?? [];

        foreach ($workflowRuns as $run) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'github',
                'metric_name' => 'workflow_run',
                'metric_value' => 1,
                'unit' => 'count',
                'dimensions' => [
                    'run_id' => $run['id'],
                    'name' => $run['name'],
                    'status' => $run['status'],
                    'conclusion' => $run['conclusion'] ?? 'none',
                    'branch' => $run['head_branch'],
                    'commit' => $run['head_sha'],
                    'actor' => $run['actor']['login'] ?? 'unknown',
                    'created_at' => $run['created_at'],
                ],
                'recorded_at' => $now,
            ]);
        }
    }

    protected function fetchReleases(Project $project, string $token, string $repo, $now): void
    {
        $response = $this->http->get("https://api.github.com/repos/{$repo}/releases", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'query' => ['per_page' => 5],
        ]);

        $releases = json_decode($response->getBody(), true) ?: [];

        foreach ($releases as $release) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'github',
                'metric_name' => 'release',
                'metric_value' => 1,
                'unit' => 'count',
                'dimensions' => [
                    'release_id' => $release['id'],
                    'tag_name' => $release['tag_name'],
                    'name' => $release['name'] ?? '',
                    'draft' => $release['draft'],
                    'prerelease' => $release['prerelease'],
                    'published_at' => $release['published_at'],
                ],
                'recorded_at' => $now,
            ]);
        }
    }

    /**
     * Handle incoming GitHub webhook event.
     */
    public function handleWebhook(Project $project, string $eventType, array $payload): void
    {
        $now = now();

        $dimensions = [];
        $metricName = '';

        switch ($eventType) {
            case 'deployment':
                $metricName = 'deployment';
                $dimensions = [
                    'deployment_id' => $payload['deployment']['id'],
                    'ref' => $payload['deployment']['ref'],
                    'environment' => $payload['deployment']['environment'],
                    'creator' => $payload['deployment']['creator']['login'] ?? 'unknown',
                    'status' => 'created',
                ];
                break;
            case 'deployment_status':
                $metricName = 'deployment_status';
                $dimensions = [
                    'deployment_id' => $payload['deployment']['id'],
                    'status' => $payload['deployment_status']['state'],
                    'environment' => $payload['deployment']['environment'],
                ];
                break;
            case 'workflow_run':
                $metricName = 'workflow_run';
                $run = $payload['workflow_run'];
                $dimensions = [
                    'run_id' => $run['id'],
                    'name' => $run['name'],
                    'status' => $run['status'],
                    'conclusion' => $run['conclusion'] ?? 'none',
                    'branch' => $run['head_branch'],
                    'actor' => $run['actor']['login'] ?? 'unknown',
                ];
                break;
            case 'release':
                $metricName = 'release';
                $dimensions = [
                    'release_id' => $payload['release']['id'],
                    'tag_name' => $payload['release']['tag_name'],
                    'published_at' => $payload['release']['published_at'],
                ];
                break;
            default:
                return;
        }

        if ($metricName) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'github',
                'metric_name' => $metricName,
                'metric_value' => 1,
                'unit' => 'count',
                'dimensions' => $dimensions,
                'recorded_at' => $now,
            ]);
        }
    }
}