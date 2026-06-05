<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 30, 'connect_timeout' => 10]);
    }

    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['cloudflare'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        $apiToken = $config['api_token'] ?? null;
        $zoneId = $config['zone_id'] ?? null;
        $accountId = $config['account_id'] ?? null;

        if (!$apiToken || !$zoneId) {
            Log::warning("Cloudflare: Missing api_token or zone_id for project {$project->name}");
            return;
        }

        Log::info("Cloudflare: Fetching metrics for project {$project->name}");

        $now = now();

        try {
            // GraphQL analytics
            $graphqlMetrics = $this->fetchZoneAnalytics($apiToken, $zoneId, $accountId);
            foreach ($graphqlMetrics as $metric) {
                IntegrationMetric::create([
                    'project_id'   => $project->id,
                    'integration'  => 'cloudflare',
                    'metric_name'  => $metric['name'],
                    'metric_value' => $metric['value'],
                    'unit'         => $metric['unit'],
                    'dimensions'   => $metric['dimensions'] ?? [],
                    'recorded_at'  => $now,
                ]);
            }

            // Security events
            $securityCount = $this->fetchSecurityEventsCount($apiToken, $zoneId);
            IntegrationMetric::create([
                'project_id'   => $project->id,
                'integration'  => 'cloudflare',
                'metric_name'  => 'security_events',
                'metric_value' => $securityCount,
                'unit'         => 'count',
                'dimensions'   => [],
                'recorded_at'  => $now,
            ]);

            Log::info("Cloudflare: Stored metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("Cloudflare: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }

    protected function fetchZoneAnalytics(string $apiToken, string $zoneId, ?string $accountId): array
    {
        $since = now()->subHours(24)->toIso8601ZuluString();

        $query = <<<'GRAPHQL'
        {
          viewer {
            zones(filter: {zoneTag: "%s"}) {
              httpRequests1hGroups(limit: 24, filter: {datetime_geq: "%s"}) {
                dimensions { datetime }
                sum { requests pageViews bytes threats cachedBytes cachedRequests }
                uniq { uniques }
              }
            }
          }
        }
        GRAPHQL;

        $query = sprintf($query, $zoneId, $since);

        $response = $this->http->post('https://api.cloudflare.com/client/v4/graphql', [
            'headers' => [
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type'  => 'application/json',
            ],
            'json' => ['query' => $query],
        ]);

        $body = json_decode($response->getBody(), true);
        $groups = $body['data']['viewer']['zones'][0]['httpRequests1hGroups'] ?? [];

        $totalRequests = 0;
        $totalPageViews = 0;
        $totalBytes = 0;
        $totalThreats = 0;
        $totalCachedBytes = 0;
        $totalCachedRequests = 0;
        $totalUniques = 0;

        foreach ($groups as $group) {
            $sum = $group['sum'] ?? [];
            $uniq = $group['uniq'] ?? [];
            $totalRequests += $sum['requests'] ?? 0;
            $totalPageViews += $sum['pageViews'] ?? 0;
            $totalBytes += $sum['bytes'] ?? 0;
            $totalThreats += $sum['threats'] ?? 0;
            $totalCachedBytes += $sum['cachedBytes'] ?? 0;
            $totalCachedRequests += $sum['cachedRequests'] ?? 0;
            $totalUniques += $uniq['uniques'] ?? 0;
        }

        $cacheHitRatio = $totalRequests > 0 ? round(($totalCachedRequests / $totalRequests) * 100, 2) : 0;

        return [
            ['name' => 'requests', 'value' => $totalRequests, 'unit' => 'count', 'dimensions' => []],
            ['name' => 'page_views', 'value' => $totalPageViews, 'unit' => 'count', 'dimensions' => []],
            ['name' => 'bandwidth_bytes', 'value' => $totalBytes, 'unit' => 'bytes', 'dimensions' => []],
            ['name' => 'threats_blocked', 'value' => $totalThreats, 'unit' => 'count', 'dimensions' => []],
            ['name' => 'cached_bytes', 'value' => $totalCachedBytes, 'unit' => 'bytes', 'dimensions' => []],
            ['name' => 'cached_requests', 'value' => $totalCachedRequests, 'unit' => 'count', 'dimensions' => []],
            ['name' => 'unique_visitors', 'value' => $totalUniques, 'unit' => 'count', 'dimensions' => []],
            ['name' => 'cache_hit_ratio_pct', 'value' => $cacheHitRatio, 'unit' => 'percent', 'dimensions' => []],
        ];
    }

    protected function fetchSecurityEventsCount(string $apiToken, string $zoneId): int
    {
        $response = $this->http->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/firewall/events", [
            'headers' => ['Authorization' => "Bearer {$apiToken}"],
            'query'   => ['per_page' => 1, 'page' => 1],
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['result_info']['total_count'] ?? 0;
    }
}