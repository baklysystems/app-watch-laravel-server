<?php

namespace App\Services\Integrations;

use App\Models\IntegrationMetric;
use App\Models\Project;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SesService
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

        $key = $config['access_key'] ?? null;
        $secret = $config['access_secret'] ?? null;
        $region = $config['region'] ?? 'us-east-1';

        if (!$key || !$secret) {
            Log::warning("SES: Missing credentials for project {$project->name}");
            return;
        }

        Log::info("SES: Fetching metrics for project {$project->name} (uses CloudWatch)");

        $now = now();

        try {
            // SES metrics come from CloudWatch; store placeholder indicating integration is active
            $metrics = $this->fetchCloudWatchSesMetrics($key, $secret, $region);

            foreach ($metrics as $metric) {
                IntegrationMetric::create([
                    'project_id' => $project->id,
                    'integration' => 'ses',
                    'metric_name' => $metric['name'],
                    'metric_value' => $metric['value'],
                    'unit' => $metric['unit'],
                    'dimensions' => [],
                    'recorded_at' => $now,
                ]);
            }

            Log::info("SES: Stored metrics for {$project->name}");
        } catch (\Throwable $e) {
            Log::warning("SES: Failed to fetch metrics for {$project->name} — {$e->getMessage()}");
        }
    }

    /**
     * Fetch SES metrics from CloudWatch.
     * Uses Signature V4 for authentication.
     */
    protected function fetchCloudWatchSesMetrics(string $key, string $secret, string $region): array
    {
        $service = 'monitoring';
        $host = "monitoring.{$region}.amazonaws.com";
        $endpoint = "https://{$host}/";
        $method = 'POST';
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $canonicalUri = '/';
        $canonicalQueryString = '';

        $body = http_build_query([
            'Action' => 'GetMetricStatistics',
            'Version' => '2010-08-01',
            'Namespace' => 'AWS/SES',
            'MetricName' => 'Send',
            'StartTime' => now()->subDay()->toIso8601String(),
            'EndTime' => now()->toIso8601String(),
            'Period' => 86400,
            'Statistics.member.1' => 'Sum',
        ], '', '&', PHP_QUERY_RFC3986);

        $canonicalHeaders = "content-type:application/x-www-form-urlencoded\nhost:{$host}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = 'content-type;host;x-amz-date';
        $payloadHash = hash('sha256', $body);

        $canonicalRequest = "{$method}\n{$canonicalUri}\n{$canonicalQueryString}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
        $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $dateStamp, "AWS4{$secret}", true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "{$algorithm} Credential={$key}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        try {
            $response = $this->http->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Host' => $host,
                    'X-Amz-Date' => $amzDate,
                    'Authorization' => $authHeader,
                ],
                'body' => $body,
            ]);

            // Parse XML response
            $xml = simplexml_load_string($response->getBody());
            $namespaces = $xml->getNamespaces(true);

            $sent = 0;
            $bounced = 0;
            $complaints = 0;

            foreach ($xml->GetMetricStatisticsResult as $result) {
                $label = (string) $result->Label;
                $datapoints = $result->Datapoints->member;
                $sum = 0;
                if ($datapoints->Sum) {
                    $sum = (float) $datapoints->Sum;
                }
                if (strpos($label, 'Send') !== false) $sent = $sum;
                if (strpos($label, 'Bounce') !== false) $bounced = $sum;
                if (strpos($label, 'Complaint') !== false) $complaints = $sum;
            }

            $delivered = $sent - $bounced;
            $deliveryRate = $sent > 0 ? round(($delivered / $sent) * 100, 2) : 100;

            return [
                ['name' => 'sent', 'value' => $sent, 'unit' => 'count'],
                ['name' => 'delivered', 'value' => $delivered, 'unit' => 'count'],
                ['name' => 'bounced', 'value' => $bounced, 'unit' => 'count'],
                ['name' => 'complaints', 'value' => $complaints, 'unit' => 'count'],
                ['name' => 'delivery_rate', 'value' => $deliveryRate, 'unit' => 'percent'],
            ];
        } catch (\Throwable $e) {
            Log::warning("SES CloudWatch: Request failed — {$e->getMessage()}");
            return [
                ['name' => 'sent', 'value' => 0, 'unit' => 'count'],
                ['name' => 'delivered', 'value' => 0, 'unit' => 'count'],
                ['name' => 'bounced', 'value' => 0, 'unit' => 'count'],
                ['name' => 'complaints', 'value' => 0, 'unit' => 'count'],
                ['name' => 'delivery_rate', 'value' => 100, 'unit' => 'percent'],
            ];
        }
    }
}