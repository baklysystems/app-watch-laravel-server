<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\TelegramSubscription;
use App\Services\IftttNotificationService;
use App\Services\N8nNotificationService;
use App\Services\TelegramNotificationService;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAlertNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Alert $alert,
        protected array $details,
        protected string $channel,
    ) {}

    public function handle(): void
    {
        match ($this->channel) {
            'mail' => $this->sendMail(),
            'slack' => $this->sendSlack(),
            'discord' => $this->sendDiscord(),
            'webhook' => $this->sendWebhook(),
            'telegram' => $this->sendTelegram(),
            'n8n' => $this->sendN8n(),
            'ifttt' => $this->sendIfttt(),
            default => null,
        };
    }

    protected function sendMail(): void
    {
        $project = $this->alert->project;

        Mail::raw($this->buildMessage(), function (Message $message) use ($project) {
            $message->subject("[Appswatch] Alert: {$this->alert->name} — {$project->name}")
                ->to($project->user?->email ?? config('mail.from.address'));
        });
    }

    protected function sendSlack(): void
    {
        $webhookUrl = config('services.slack.webhook_url');
        if (!$webhookUrl) return;

        $client = new Client(['timeout' => 10]);
        $client->post($webhookUrl, [
            'json' => [
                'text' => $this->buildMessage(),
                'username' => 'Appswatch Alerts',
                'icon_emoji' => ':warning:',
            ],
        ]);
    }

    protected function sendDiscord(): void
    {
        $webhookUrl = config('services.discord.webhook_url');
        if (!$webhookUrl) return;

        $client = new Client(['timeout' => 10]);
        $client->post($webhookUrl, [
            'json' => [
                'content' => $this->buildMessage(),
                'username' => 'Appswatch Alerts',
            ],
        ]);
    }

    protected function sendTelegram(): void
    {
        $project = $this->alert->project;
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            Log::warning('Telegram bot token not configured, skipping alert delivery.');
            return;
        }

        $telegram = app(TelegramNotificationService::class);

        // Send to all active Telegram subscribers for this project
        $subscriptions = TelegramSubscription::where('project_id', $project->id)
            ->where('is_active', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            // Fallback to default chat ID if configured
            $defaultChatId = config('services.telegram.default_chat_id');
            if ($defaultChatId) {
                $telegram->sendAlert($defaultChatId, $this->alert, $project, $this->details);
            } else {
                Log::info('No Telegram subscribers for project ' . $project->name . ' and no default chat ID configured.');
            }
            return;
        }

        foreach ($subscriptions as $subscription) {
            $telegram->sendAlert($subscription->chat_id, $this->alert, $project, $this->details);
        }
    }

    protected function sendN8n(): void
    {
        $project = $this->alert->project;
        $webhookUrl = $this->alert->conditions['n8n_webhook_url'] ?? config('services.n8n.default_webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('N8N webhook URL not configured, skipping alert delivery.');
            return;
        }

        $n8n = app(N8nNotificationService::class);
        $n8n->sendAlert($webhookUrl, $this->alert, $project, $this->details);
    }

    protected function sendIfttt(): void
    {
        $project = $this->alert->project;
        $webhookKey = $this->alert->conditions['ifttt_key'] ?? config('services.ifttt.webhook_key');
        $eventName = $this->alert->conditions['ifttt_event_name'] ?? config('services.ifttt.event_name', 'appswatch_alert');

        if (empty($webhookKey)) {
            Log::warning('IFTTT webhook key not configured, skipping alert delivery.');
            return;
        }

        $ifttt = app(IftttNotificationService::class);
        $ifttt->sendAlert($webhookKey, $eventName, $this->alert, $project, $this->details);
    }

    protected function sendWebhook(): void
    {
        $webhookUrl = $this->alert->conditions['webhook_url'] ?? null;
        if (!$webhookUrl) return;

        $client = new Client(['timeout' => 10]);
        $client->post($webhookUrl, [
            'json' => [
                'alert' => $this->alert->name,
                'type' => $this->alert->type,
                'project' => $this->alert->project->name,
                'details' => $this->details,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    protected function buildMessage(): string
    {
        $project = $this->alert->project;
        $lines = [
            "🚨 Alert: **{$this->alert->name}**",
            "Project: {$project->name}",
            "Type: {$this->alert->type}",
            "",
        ];

        foreach ($this->details as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }

        return implode("\n", $lines);
    }
}