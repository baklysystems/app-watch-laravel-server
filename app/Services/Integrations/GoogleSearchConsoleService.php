<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 30, 'connect_timeout' => 10]);
    }

    public function fetchAndStoreMetrics(Project $project): void
    {
        $config = $project->integrations_config['google_search_console'] ?? [];
        if (empty($config['enabled'] ?? false)) return;

        Log::info("GSC: Fetching metrics for project {$project->name}");

        $now = now();
        $siteUrl = $config['site_url'] ?? null;
        $accessToken = $config['access_token'] ?? null;

        if (!$siteUrl || !$accessToken) {
            Log::warning("GSC: Missing site_url or access_token for project {$project->name}");
            return;
        }

        try {
            $analytics = $this->fetchSearchAnalytics($siteUrl, $accessToken);
            foreach ($analytics['aggregates'] ?? [] as $metric) {
                IntegrationMetric::create([
                    'project_id'   => $project->id,
                    'integration'  => 'google_search_console',
                    'metric_name'  => $metric['name'],
                    'metric_value' => $metric['value'],
                    'unit'         => $metric['unit'],
                    'dimensions'   => [],
                    'recorded_at'  => $now,
                ]);
            }

            // Store top 20 queries
            foreach ($analytics['top_queries'] ?? [] as $i => $query) {
                IntegrationMetric::create([
                    'project_id'   => $project->id,
                    'integration'  => 'google_search_console',
                    'metric_name'  => 'top_query',
                    'metric_value' => $query['clicks'],
                    'unit'         => 'clicks',
                    'dimensions'   => ['rank' => $i + 1, 'query' => $query['query'], 'impressions' => $query['impressions'], 'ctr' => $query['ctr'], 'position' => $query['position']],
                    'recorded_at'  => $now,
                ]);
            }

            // Store top 20 pages
            foreach ($analytics['top_pages'] ?? [] as $i => $page) {
                IntegrationMetric::create([
                    'project_id'   => $project->id,
                    'integration'  => 'google_search_console',
                    'metric_name'  => 'top_page',
                    'metric_value' => $page['clicks'],
                    'unit'         => 'clicks',
                    'dimensions'   => ['rank' => $i + 1, 'page' => $page['page'], 'impressions' => $page['impressions'], 'ctr' => $page['ctr'], 'position' => $page['position']],
                    'recorded_at'  => $now,
                ]);
            }

            Log::info("GSC: Stored " . (count($analytics['aggregates'] ?? []) + count($analytics['top_queries'] ?? []) + count($analytics['top_pages'] ?? [])) . " metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("GSC: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }

    protected function fetchSearchAnalytics(string $siteUrl, string $accessToken, int $days = 28): array
    {
        $endDate = now()->format('Y-m-d');
        $startDate = now()->subDays($days)->format('Y-m-d');

        $response = $this->http->post('https://www.googleapis.com/webmasters/v3/sites/' . urlencode($siteUrl) . '/searchAnalytics/query', [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'startDate'  => $startDate,
                'endDate'    => $endDate,
                'dimensions' => ['query', 'page', 'country', 'device'],
                'rowLimit'   => 10000,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $rows = $data['rows'] ?? [];

        $totalClicks = array_sum(array_column($rows, 'clicks'));
        $totalImpressions = array_sum(array_column($rows, 'impressions'));
        $avgPosition = $totalImpressions > 0
            ? array_sum(array_map(fn($r) => $r['position'] * $r['impressions'], $rows)) / $totalImpressions
            : 0;
        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

        $aggregates = [
            ['name' => 'clicks', 'value' => $totalClicks, 'unit' => 'count'],
            ['name' => 'impressions', 'value' => $totalImpressions, 'unit' => 'count'],
            ['name' => 'ctr', 'value' => $ctr, 'unit' => 'percent'],
            ['name' => 'avg_position', 'value' => round($avgPosition, 1), 'unit' => 'rank'],
        ];

        // Aggregate by query
        $queryAgg = [];
        foreach ($rows as $row) {
            $key = $row['keys'][0];
            $queryAgg[$key] = ($queryAgg[$key] ?? 0) + ($row['clicks'] ?? 0);
        }
        arsort($queryAgg);
        $topQueriesRows = [];
        foreach ($rows as $row) {
            if ($row['keys'][0] === array_key_first($queryAgg)) {
                $topQueriesRows[] = $row;
            }
        }
        // Use actual row data for top queries
        $byQuery = [];
        foreach ($rows as $row) {
            $q = $row['keys'][0];
            if (!isset($byQuery[$q])) $byQuery[$q] = ['clicks' => 0, 'impressions' => 0, 'positions' => 0, 'count' => 0];
            $byQuery[$q]['clicks'] += $row['clicks'];
            $byQuery[$q]['impressions'] += $row['impressions'];
            $byQuery[$q]['positions'] += $row['position'] * $row['impressions'];
            $byQuery[$q]['count']++;
        }
        $topQueries = [];
        uasort($byQuery, fn($a, $b) => $b['clicks'] <=> $a['clicks']);
        foreach (array_slice($byQuery, 0, 20) as $query => $stats) {
            $topQueries[] = [
                'query'       => $query,
                'clicks'      => $stats['clicks'],
                'impressions' => $stats['impressions'],
                'ctr'         => $stats['impressions'] > 0 ? round(($stats['clicks'] / $stats['impressions']) * 100, 2) : 0,
                'position'    => $stats['impressions'] > 0 ? round($stats['positions'] / $stats['impressions'], 1) : 0,
            ];
        }

        // Aggregate by page
        $byPage = [];
        foreach ($rows as $row) {
            $p = $row['keys'][1];
            if (!isset($byPage[$p])) $byPage[$p] = ['clicks' => 0, 'impressions' => 0, 'positions' => 0];
            $byPage[$p]['clicks'] += $row['clicks'];
            $byPage[$p]['impressions'] += $row['impressions'];
            $byPage[$p]['positions'] += $row['position'] * $row['impressions'];
        }
        uasort($byPage, fn($a, $b) => $b['clicks'] <=> $a['clicks']);
        $topPages = [];
        foreach (array_slice($byPage, 0, 20) as $page => $stats) {
            $topPages[] = [
                'page'        => $page,
                'clicks'      => $stats['clicks'],
                'impressions' => $stats['impressions'],
                'ctr'         => $stats['impressions'] > 0 ? round(($stats['clicks'] / $stats['impressions']) * 100, 2) : 0,
                'position'    => $stats['impressions'] > 0 ? round($stats['positions'] / $stats['impressions'], 1) : 0,
            ];
        }

        return compact('aggregates', 'topQueries', 'topPages');
    }
}