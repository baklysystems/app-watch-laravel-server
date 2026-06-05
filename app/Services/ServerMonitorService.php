<?php

namespace App\Services;

use App\Models\IntegrationMetric;
use App\Models\Project;

class ServerMonitorService
{
    /**
     * Collect server metrics for all configured hosts across projects.
     *
     * Uses PHP's built-in functions to gather system metrics.
     * For remote servers, SSH-based collection would be needed (future enhancement).
     * For the local dashboard server, we can collect metrics directly.
     */
    public function collectAll(): array
    {
        $results = [];
        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $config = $project->integrations_config['server_monitor'] ?? null;
            if (!$config || !($config['enabled'] ?? false)) {
                continue;
            }

            foreach ($config['hosts'] ?? [] as $host) {
                $results[] = $this->collectHost($project, $host);
            }
        }

        return $results;
    }

    /**
     * Collect metrics from a single host.
     * For localhost or hosts without SSH config, collects from the dashboard server itself.
     */
    public function collectHost(Project $project, array $hostConfig): array
    {
        $host = $hostConfig['host'] ?? 'localhost';

        // For the MVP, collect local server metrics
        $metrics = $this->gatherLocalMetrics();

        foreach ($metrics as $metric) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'server_monitor',
                'metric_name' => $metric['name'],
                'metric_value' => $metric['value'],
                'unit' => $metric['unit'],
                'dimensions' => ['host' => $host],
                'recorded_at' => now(),
            ]);
        }

        return [
            'host' => $host,
            'project' => $project->slug,
            'metrics' => $metrics,
        ];
    }

    /**
     * Gather local server metrics using PHP built-in functions.
     */
    protected function gatherLocalMetrics(): array
    {
        $metrics = [];

        // CPU load average
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $metrics[] = ['name' => 'cpu_load_1m', 'value' => (float) $load[0], 'unit' => 'load'];
            $metrics[] = ['name' => 'cpu_load_5m', 'value' => (float) $load[1], 'unit' => 'load'];
            $metrics[] = ['name' => 'cpu_load_15m', 'value' => (float) $load[2], 'unit' => 'load'];
        }

        // Memory usage
        $memTotal = $this->getMeminfo('MemTotal');
        $memAvailable = $this->getMeminfo('MemAvailable');
        $memFree = $this->getMeminfo('MemFree');

        if ($memTotal > 0) {
            $memUsed = $memTotal - ($memAvailable ?: $memFree);
            $memPct = round(($memUsed / $memTotal) * 100, 2);
            $metrics[] = ['name' => 'memory_total_mb', 'value' => round($memTotal / 1024, 2), 'unit' => 'MB'];
            $metrics[] = ['name' => 'memory_used_mb', 'value' => round($memUsed / 1024, 2), 'unit' => 'MB'];
            $metrics[] = ['name' => 'memory_usage_pct', 'value' => $memPct, 'unit' => '%'];
        }

        // Disk usage (root partition)
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        if ($diskTotal > 0) {
            $diskUsed = $diskTotal - $diskFree;
            $diskPct = round(($diskUsed / $diskTotal) * 100, 2);
            $metrics[] = ['name' => 'disk_total_gb', 'value' => round($diskTotal / 1024 / 1024 / 1024, 2), 'unit' => 'GB'];
            $metrics[] = ['name' => 'disk_used_gb', 'value' => round($diskUsed / 1024 / 1024 / 1024, 2), 'unit' => 'GB'];
            $metrics[] = ['name' => 'disk_usage_pct', 'value' => $diskPct, 'unit' => '%'];
        }

        return $metrics;
    }

    /**
     * Parse a value from /proc/meminfo (Linux) or fallback.
     */
    protected function getMeminfo(string $key): int
    {
        $file = '/proc/meminfo';
        if (!is_readable($file)) {
            return 0;
        }

        $content = @file_get_contents($file);
        if (!$content) {
            return 0;
        }

        if (preg_match('/^' . preg_quote($key, '/') . ':\s+(\d+)\s*kB/m', $content, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}