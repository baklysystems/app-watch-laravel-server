<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    protected string $botToken;
    protected string $dashboardUrl;
    protected Client $http;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->dashboardUrl = rtrim(config('services.telegram.dashboard_url', config('app.url', 'http://localhost')), '/');
        $this->http = new Client([
            'base_uri' => "https://api.telegram.org/bot{$this->botToken}/",
            'timeout' => 15,
        ]);
    }

    /**
     * Send an alert notification to a Telegram chat with inline action buttons.
     */
    public function sendAlert(string $chatId, $alert, $project, array $details): bool
    {
        $text = $this->buildAlertMessage($alert, $project, $details);
        $keyboard = $this->buildAlertKeyboard($alert, $project);

        return $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Send a project health status report to a Telegram chat.
     */
    public function sendStatusReport(string $chatId, array $health): bool
    {
        $lines = [
            "📊 *{$health['project_name']}* Health Report",
            str_repeat('━', 24),
            "",
        ];

        $exceptionEmoji = $health['exceptions_today'] > 10 ? '🔴' : ($health['exceptions_today'] > 3 ? '🟡' : '🟢');
        $lines[] = "{$exceptionEmoji} Exceptions: {$health['exceptions_today']} today ({$health['exceptions_new']} new)";

        $uptimeEmoji = $health['uptime_pct'] >= 99.9 ? '🟢' : ($health['uptime_pct'] >= 99.0 ? '🟡' : '🔴');
        $lines[] = "{$uptimeEmoji} Uptime: {$health['uptime_pct']}% (last 24h)";

        $responseEmoji = $health['avg_response_time'] < 200 ? '🟢' : ($health['avg_response_time'] < 500 ? '🟡' : '🔴');
        $lines[] = "{$responseEmoji} Avg Response: {$health['avg_response_time']}ms";

        $queueEmoji = $health['queue_failures'] > 0 ? '🔴' : '🟢';
        $lines[] = "{$queueEmoji} Queue Failures: {$health['queue_failures']}";

        $text = implode("\n", $lines);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Refresh', 'callback_data' => 'status:refresh'],
                    ['text' => '📋 Exceptions', 'callback_data' => 'exceptions:latest'],
                ],
            ],
        ];

        return $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Send exception list to a Telegram chat (paginated).
     */
    public function sendExceptionList(string $chatId, array $exceptions, int $page, int $totalPages): bool
    {
        if (empty($exceptions)) {
            return $this->sendMessage($chatId, '✅ No unresolved exceptions.', null);
        }

        $lines = [];
        foreach ($exceptions as $ex) {
            $emoji = $ex['severity'] === 'critical' || $ex['severity'] === 'error' ? '🔴' : '🟡';
            $lines[] = "{$emoji} *{$ex['class']}* × {$ex['occurrence_count']}";
            $lines[] = "`{$ex['message']}`";
            $lines[] = "First: {$ex['first_seen']} | Last: {$ex['last_seen']}";
            $lines[] = '';

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Resolve', 'callback_data' => "resolve:{$ex['id']}"],
                        ['text' => '🔇 Mute 1h', 'callback_data' => "mute:{$ex['id']}:60"],
                    ],
                    [
                        ['text' => '🔇 Mute 24h', 'callback_data' => "mute:{$ex['id']}:1440"],
                        ['text' => '📊 Details', 'url' => "{$this->dashboardUrl}/exceptions/{$ex['id']}"],
                    ],
                ],
            ];
        }

        // Pagination row
        $navRow = [];
        if ($page > 1) {
            $navRow[] = ['text' => '◀️ Previous', 'callback_data' => "exceptions:page:" . ($page - 1)];
        }
        $navRow[] = ['text' => "Page {$page}/{$totalPages}", 'callback_data' => 'exceptions:nop'];
        if ($page < $totalPages) {
            $navRow[] = ['text' => 'Next ▶️', 'callback_data' => "exceptions:page:" . ($page + 1)];
        }

        $text = implode("\n", $lines);

        $keyboard = [
            'inline_keyboard' => [
                $navRow,
            ],
        ];

        return $this->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Send a simple text message.
     */
    public function sendSimpleMessage(string $chatId, string $text): bool
    {
        return $this->sendMessage($chatId, $text, null);
    }

    /**
     * Build the alert notification text.
     */
    protected function buildAlertMessage($alert, $project, array $details): string
    {
        $lines = [
            "🚨 *Alert: {$alert->name}*",
            "📁 Project: {$project->name}",
            "🏷️ Type: {$alert->type}",
            "🕐 Time: " . now()->format('Y-m-d H:i:s T'),
            "",
        ];

        foreach ($details as $key => $value) {
            $lines[] = "• {$key}: {$value}";
        }

        return implode("\n", $lines);
    }

    /**
     * Build inline keyboard for alert messages.
     */
    protected function buildAlertKeyboard($alert, $project): array
    {
        $exceptionUrl = '';
        if (!empty($alert->conditions['exception_id'] ?? null)) {
            $exceptionUrl = "{$this->dashboardUrl}/exceptions/{$alert->conditions['exception_id']}";
        } else {
            $exceptionUrl = "{$this->dashboardUrl}/projects/{$project->id}/exceptions";
        }

        return [
            'inline_keyboard' => [
                [
                    ['text' => '🔍 View in Appswatch', 'url' => $exceptionUrl],
                ],
            ],
        ];
    }

    /**
     * Send a message via the Telegram Bot API.
     */
    protected function sendMessage(string $chatId, string $text, ?array $replyMarkup): bool
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = $this->http->post('sendMessage', [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!($body['ok'] ?? false)) {
                Log::warning('Telegram sendMessage failed', [
                    'chat_id' => $chatId,
                    'error' => $body['description'] ?? 'Unknown error',
                ]);
                return false;
            }

            return true;
        } catch (GuzzleException $e) {
            Log::error('Telegram API request failed: ' . $e->getMessage(), [
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    /**
     * Set the webhook URL for this bot.
     */
    public function setWebhook(string $webhookUrl, string $secretToken): bool
    {
        try {
            $response = $this->http->post('setWebhook', [
                'json' => [
                    'url' => $webhookUrl,
                    'secret_token' => $secretToken,
                    'allowed_updates' => ['message', 'callback_query'],
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!($body['ok'] ?? false)) {
                Log::warning('Telegram setWebhook failed', [
                    'error' => $body['description'] ?? 'Unknown error',
                ]);
                return false;
            }

            return true;
        } catch (GuzzleException $e) {
            Log::error('Telegram setWebhook request failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current webhook info.
     */
    public function getWebhookInfo(): array
    {
        try {
            $response = $this->http->get('getWebhookInfo');
            return json_decode($response->getBody()->getContents(), true)['result'] ?? [];
        } catch (GuzzleException $e) {
            Log::error('Telegram getWebhookInfo failed: ' . $e->getMessage());
            return [];
        }
    }
}