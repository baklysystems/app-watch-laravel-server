<?php

namespace App\Services;

use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Support\Facades\Config;

class ServiceVitalsService
{
    /**
     * Check all service vitals for all active projects.
     */
    public function checkAll(): array
    {
        $results = [];
        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $config = $project->integrations_config['service_vitals'] ?? null;
            if (!$config || !($config['enabled'] ?? false)) {
                continue;
            }

            $results[$project->slug] = $this->checkProject($project);
        }

        return $results;
    }

    /**
     * Check all vitals for a single project.
     */
    public function checkProject(Project $project): array
    {
        $results = [];
        $checks = ['mail', 'queue', 'notification', 'redis', 'reverb'];

        foreach ($checks as $service) {
            $method = 'check' . ucfirst($service);
            $status = $this->{$method}($project);
            $results[$service] = $status;

            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'service_vitals',
                'metric_name' => $service . '_status',
                'metric_value' => $status['ok'] ? 1 : 0,
                'unit' => 'bool',
                'dimensions' => $status['details'] ?? [],
                'recorded_at' => now(),
            ]);
        }

        return $results;
    }

    /**
     * Check email/mail configuration.
     */
    public function checkMail(Project $project): array
    {
        $config = $project->integrations_config['service_vitals'] ?? [];
        $driver = Config::get('mail.default', 'smtp');
        $mailConfig = Config::get("mail.mailers.{$driver}", []);

        $host = $mailConfig['host'] ?? null;
        $port = $mailConfig['port'] ?? null;

        if ($host && $port) {
            $connected = $this->canConnect($host, (int)$port, 3);
            return [
                'ok' => $connected,
                'details' => [
                    'driver' => $driver,
                    'host' => $host,
                    'port' => $port,
                    'status' => $connected ? 'connected' : 'unreachable',
                ],
            ];
        }

        return [
            'ok' => false,
            'details' => [
                'driver' => $driver,
                'status' => 'not_configured',
            ],
        ];
    }

    /**
     * Check queue worker status.
     */
    public function checkQueue(Project $project): array
    {
        $driver = Config::get('queue.default', 'sync');
        $connection = Config::get("queue.connections.{$driver}", []);
        $configured = !empty($connection);

        // Check if queue is running by looking at jobs table
        try {
            $queueSize = \App\Models\QueueJob::where('project_id', $project->id)
                ->where('status', 'pending')
                ->count();
        } catch (\Throwable $e) {
            $queueSize = -1;
        }

        return [
            'ok' => $configured,
            'details' => [
                'driver' => $driver,
                'pending_jobs' => $queueSize,
                'status' => $configured ? 'configured' : 'not_configured',
            ],
        ];
    }

    /**
     * Check notification service status (channels configured).
     */
    public function checkNotification(Project $project): array
    {
        $channels = [];
        $defaults = Config::get('notification.defaults', []);

        foreach (['mail', 'slack', 'discord', 'webhook'] as $channel) {
            $channels[$channel] = Config::has("notification.channels.{$channel}");
        }

        $enabledCount = count(array_filter($channels));

        return [
            'ok' => $enabledCount > 0,
            'details' => [
                'channels_configured' => $enabledCount,
                'channels' => $channels,
                'status' => $enabledCount > 0 ? "{$enabledCount} channels" : 'none',
            ],
        ];
    }

    /**
     * Check Redis connection.
     */
    public function checkRedis(Project $project): array
    {
        $redisHost = Config::get('database.redis.default.host', '127.0.0.1');
        $redisPort = (int)Config::get('database.redis.default.port', 6379);
        $redisConfigured = $redisHost && $redisPort;

        if ($redisConfigured) {
            $connected = $this->canConnect($redisHost, $redisPort, 2);
            return [
                'ok' => $connected,
                'details' => [
                    'host' => $redisHost,
                    'port' => $redisPort,
                    'status' => $connected ? 'connected' : 'unreachable',
                ],
            ];
        }

        return [
            'ok' => false,
            'details' => [
                'status' => 'not_configured',
            ],
        ];
    }

    /**
     * Check Reverb WebSocket status.
     */
    public function checkReverb(Project $project): array
    {
        $config = Config::get('reverb', []);
        $apps = $config['apps'] ?? [];
        $configured = !empty($apps);

        if ($configured) {
            $firstApp = reset($apps);
            $host = $config['host'] ?? '127.0.0.1';
            $port = (int)($config['port'] ?? 8080);

            $connected = $this->canConnect($host, $port, 2);
            return [
                'ok' => $connected,
                'details' => [
                    'host' => $host,
                    'port' => $port,
                    'app_id' => $firstApp['app_id'] ?? null,
                    'status' => $connected ? 'running' : 'unreachable',
                ],
            ];
        }

        return [
            'ok' => false,
            'details' => [
                'status' => 'not_configured',
            ],
        ];
    }

    /**
     * Test connection to a host:port using socket.
     */
    protected function canConnect(string $host, int $port, int $timeout = 3): bool
    {
        try {
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if ($socket) {
                fclose($socket);
                return true;
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}