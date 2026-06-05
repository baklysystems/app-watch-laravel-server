<?php

namespace App\Services;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class UptimeService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'http_errors' => false,
            'connect_timeout' => 10,
            'timeout' => 15,
            'verify' => false,
        ]);
    }

    /**
     * Check all configured URLs across all active projects.
     */
    public function checkAll(): array
    {
        $results = [];
        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $config = $project->integrations_config['uptime'] ?? null;
            if (!$config || !($config['enabled'] ?? false)) {
                continue;
            }

            foreach ($config['urls'] ?? [] as $url) {
                $results[] = $this->checkUrl($project, $url);
                usleep(100000); // 100ms pause between checks
            }
        }

        return $results;
    }

    /**
     * Check a single URL for a project.
     */
    public function checkUrl(Project $project, string $url): array
    {
        $start = microtime(true);

        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $duration = round((microtime(true) - $start) * 1000, 2);
            $success = $statusCode >= 200 && $statusCode < 400;
        } catch (ConnectException $e) {
            $statusCode = 0;
            $duration = round((microtime(true) - $start) * 1000, 2);
            $success = false;
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $duration = round((microtime(true) - $start) * 1000, 2);
            $success = false;
        } catch (\Throwable $e) {
            $statusCode = 0;
            $duration = round((microtime(true) - $start) * 1000, 2);
            $success = false;
        }

        // Store metrics
        IntegrationMetric::create([
            'project_id' => $project->id,
            'integration' => 'uptime',
            'metric_name' => 'status_code',
            'metric_value' => $statusCode,
            'unit' => 'code',
            'dimensions' => ['url' => $url],
            'recorded_at' => now(),
        ]);

        IntegrationMetric::create([
            'project_id' => $project->id,
            'integration' => 'uptime',
            'metric_name' => 'response_time_ms',
            'metric_value' => $duration,
            'unit' => 'ms',
            'dimensions' => ['url' => $url],
            'recorded_at' => now(),
        ]);

        IntegrationMetric::create([
            'project_id' => $project->id,
            'integration' => 'uptime',
            'metric_name' => 'is_up',
            'metric_value' => $success ? 1 : 0,
            'unit' => 'bool',
            'dimensions' => ['url' => $url],
            'recorded_at' => now(),
        ]);

        return [
            'url' => $url,
            'project' => $project->slug,
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'success' => $success,
        ];
    }
}