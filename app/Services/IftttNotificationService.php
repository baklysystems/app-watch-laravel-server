<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class IftttNotificationService
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 15]);
    }

    /**
     * Trigger an IFTTT webhook by event name.
     * IFTTT Webhooks use: POST https://maker.ifttt.com/trigger/{event}/with/key/{key}
     */
    public function trigger(string $key, string $eventName, array $values = []): bool
    {
        // Support both full URLs and key-only configs
        if (filter_var($key, FILTER_VALIDATE_URL)) {
            $url = $key;
        } else {
            $url = "https://maker.ifttt.com/trigger/{$eventName}/with/key/{$key}";
        }

        try {
            $response = $this->http->post($url, [
                'json' => [
                    'value1' => $values['value1'] ?? '',
                    'value2' => $values['value2'] ?? '',
                    'value3' => $values['value3'] ?? '',
                ],
            ]);

            Log::debug('IFTTT webhook triggered', [
                'event' => $eventName,
                'status' => $response->getStatusCode(),
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (GuzzleException $e) {
            Log::error('IFTTT webhook trigger failed: ' . $e->getMessage(), [
                'event' => $eventName,
            ]);
            return false;
        }
    }

    /**
     * Send an alert notification via IFTTT.
     */
    public function sendAlert(string $key, string $eventName, $alert, $project, array $details): bool
    {
        $appUrl = rtrim(config('app.url', 'http://localhost'), '/');

        return $this->trigger($key, $eventName, [
            'value1' => "Alert: {$alert->name}",
            'value2' => "Project: {$project->name} | Type: {$alert->type}",
            'value3' => json_encode($details) . " | {$appUrl}/projects/{$project->id}/exceptions",
        ]);
    }
}