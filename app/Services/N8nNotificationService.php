<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class N8nNotificationService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 15]);
    }

    /**
     * Send an alert notification to an N8N webhook.
     */
    public function sendAlert(string $webhookUrl, $alert, $project, array $details): bool
    {
        $payload = [
            'event' => 'alert.triggered',
            'timestamp' => now()->toIso8601String(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'environment' => $project->environment ?? 'production',
                'slug' => $project->slug ?? '',
            ],
            'alert' => [
                'id' => $alert->id,
                'name' => $alert->name,
                'type' => $alert->type,
                'conditions' => $alert->conditions,
                'channel' => 'n8n',
            ],
            'details' => $details,
            'appswatch_url' => rtrim(config('app.url', 'http://localhost'), '/') . '/projects/' . $project->id . '/exceptions',
        ];

        return $this->post($webhookUrl, $payload);
    }

    /**
     * Send a generic event to an N8N webhook.
     */
    public function sendEvent(string $webhookUrl, string $eventType, array $data): bool
    {
        $payload = array_merge([
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
        ], $data);

        return $this->post($webhookUrl, $payload);
    }

    /**
     * POST payload to a webhook URL.
     */
    protected function post(string $webhookUrl, array $payload): bool
    {
        try {
            $headers = config('services.n8n.webhook_headers', []);
            $options = [
                'json' => $payload,
                'headers' => array_filter($headers),
            ];

            $response = $this->http->post($webhookUrl, $options);

            Log::debug('N8N webhook delivered', [
                'url' => $webhookUrl,
                'status' => $response->getStatusCode(),
                'event' => $payload['event'] ?? 'unknown',
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (GuzzleException $e) {
            Log::error('N8N webhook delivery failed: ' . $e->getMessage(), [
                'url' => $webhookUrl,
                'event' => $payload['event'] ?? 'unknown',
            ]);
            return false;
        }
    }
}