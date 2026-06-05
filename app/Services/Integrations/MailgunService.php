<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MailgunService
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

        $domain = $config['domain'] ?? null;
        $apiKey = $config['api_key'] ?? null;
        $region = $config['region'] ?? 'us';

        if (!$domain || !$apiKey) {
            Log::warning("Mailgun: Missing domain or api_key for project {$project->name}");
            return;
        }

        $baseUrl = $region === 'eu' ? 'https://api.eu.mailgun.net/v3' : 'https://api.mailgun.net/v3';

        Log::info("Mailgun: Fetching metrics for project {$project->name}");

        $now = now();

        try {
            $response = $this->http->get("{$baseUrl}/{$domain}/stats/total", [
                'auth' => ['api', $apiKey],
                'query' => [
                    'event' => 'delivered,failed,opened,clicked,complained,unsubscribed',
                    'duration' => '1d',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $stats = $data['stats'][0] ?? null;

            if ($stats) {
                $delivered = $stats['delivered']['total'] ?? 0;
                $failedPermanent = $stats['failed']['permanent']['total'] ?? 0;
                $failedTemporary = $stats['failed']['temporary']['total'] ?? 0;
                $totalFailed = $failedPermanent + $failedTemporary;
                $opened = $stats['opened']['total'] ?? 0;
                $clicked = $stats['clicked']['total'] ?? 0;
                $complained = $stats['complained']['total'] ?? 0;
                $unsubscribed = $stats['unsubscribed']['total'] ?? 0;
                $totalSent = $delivered + $totalFailed;
                $deliveryRate = $totalSent > 0 ? round(($delivered / $totalSent) * 100, 2) : 100;

                $metrics = [
                    ['name' => 'delivered', 'value' => $delivered, 'unit' => 'count'],
                    ['name' => 'failed_permanent', 'value' => $failedPermanent, 'unit' => 'count'],
                    ['name' => 'failed_temporary', 'value' => $failedTemporary, 'unit' => 'count'],
                    ['name' => 'total_failed', 'value' => $totalFailed, 'unit' => 'count'],
                    ['name' => 'opened', 'value' => $opened, 'unit' => 'count'],
                    ['name' => 'clicked', 'value' => $clicked, 'unit' => 'count'],
                    ['name' => 'complained', 'value' => $complained, 'unit' => 'count'],
                    ['name' => 'unsubscribed', 'value' => $unsubscribed, 'unit' => 'count'],
                    ['name' => 'delivery_rate', 'value' => $deliveryRate, 'unit' => 'percent'],
                ];

                foreach ($metrics as $metric) {
                    IntegrationMetric::create([
                        'project_id' => $project->id,
                        'integration' => 'mailgun',
                        'metric_name' => $metric['name'],
                        'metric_value' => $metric['value'],
                        'unit' => $metric['unit'],
                        'dimensions' => [],
                        'recorded_at' => $now,
                    ]);
                }
            }

            Log::info("Mailgun: Stored metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("Mailgun: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }
}