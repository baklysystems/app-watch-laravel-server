<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exception;
use App\Models\IntegrationMetric;
use App\Models\Project;
use App\Models\TelegramSubscription;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramNotificationService $telegram;

    public function __construct(TelegramNotificationService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Handle incoming Telegram webhook updates.
     */
    public function handle(Request $request): void
    {
        // Verify webhook authenticity
        $secretToken = config('services.telegram.webhook_secret');
        if ($secretToken && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secretToken) {
            Log::warning('Telegram webhook: invalid secret token');
            abort(403);
        }

        $update = $request->all();

        Log::debug('Telegram webhook received', $update);

        // Handle callback queries (inline button clicks)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return;
        }

        // Handle messages
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
            return;
        }
    }

    /**
     * Handle text messages and bot commands.
     */
    protected function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $chatType = $message['chat']['type'] ?? 'private';

        // Check if this is a bot command
        $entities = $message['entities'] ?? [];
        $isCommand = false;
        foreach ($entities as $entity) {
            if (($entity['type'] ?? '') === 'bot_command') {
                $isCommand = true;
                break;
            }
        }

        if ($isCommand) {
            $this->handleCommand($chatId, $text, $chatType, $message['chat']);
        } else {
            // Non-command text — respond helpfully
            $this->telegram->sendSimpleMessage(
                $chatId,
                "👋 Hi! I'm the Appswatch monitoring bot.\n\nUse /help to see available commands, or /start to get set up."
            );
        }
    }

    /**
     * Handle bot commands.
     */
    protected function handleCommand(string $chatId, string $text, string $chatType, array $chatInfo): void
    {
        $parts = explode(' ', $text);
        $command = $parts[0] ?? '';
        $args = array_slice($parts, 1);

        // Handle command with bot username suffix (e.g., /start@appswatch_bot)
        if (str_contains($command, '@')) {
            $command = explode('@', $command)[0];
        }

        switch ($command) {
            case '/start':
                $this->cmdStart($chatId, $chatType, $chatInfo);
                break;
            case '/status':
                $this->cmdStatus($chatId, $args);
                break;
            case '/exceptions':
                $this->cmdExceptions($chatId, $args);
                break;
            case '/resolve':
                $this->cmdResolve($chatId, $args);
                break;
            case '/mute':
                $this->cmdMute($chatId, $args);
                break;
            case '/backup':
                $this->cmdBackup($chatId, $args);
                break;
            case '/uptime':
                $this->cmdUptime($chatId, $args);
                break;
            case '/metrics':
                $this->cmdMetrics($chatId, $args);
                break;
            case '/help':
                $this->cmdHelp($chatId);
                break;
            case '/subscribe':
                $this->cmdSubscribe($chatId, $args);
                break;
            case '/unsubscribe':
                $this->cmdUnsubscribe($chatId);
                break;
            case '/projects':
                $this->cmdProjects($chatId);
                break;
            default:
                $this->telegram->sendSimpleMessage(
                    $chatId,
                    "Unknown command. Use /help to see available commands."
                );
        }
    }

    /**
     * Handle inline keyboard callback queries.
     */
    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;

        if (!$chatId || !$data) {
            return;
        }

        // Answer the callback query to remove loading state
        $this->answerCallbackQuery($callbackId);

        $parts = explode(':', $data);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'resolve':
                $this->callbackResolve($chatId, $parts[1] ?? '');
                break;
            case 'mute':
                $this->callbackMute($chatId, $parts[1] ?? '', intval($parts[2] ?? 60));
                break;
            case 'status':
                $this->cmdStatus($chatId, []);
                break;
            case 'exceptions':
                $page = intval($parts[2] ?? 1);
                $this->cmdExceptions($chatId, [(string)$page]);
                break;
            case 'nop':
                // No operation — just close the loading indicator
                break;
        }
    }

    /**
     * Answer callback query to dismiss loading indicator.
     */
    protected function answerCallbackQuery(string $callbackId, string $text = ''): void
    {
        try {
            $token = config('services.telegram.bot_token');
            $http = new \GuzzleHttp\Client(['timeout' => 10]);
            $http->post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
                'json' => [
                    'callback_query_id' => $callbackId,
                    'text' => $text,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to answer Telegram callback: ' . $e->getMessage());
        }
    }

    // ─── Command Handlers ───────────────────────────────────────────

    protected function cmdStart(string $chatId, string $chatType, array $chatInfo): void
    {
        // Auto-subscribe to default project
        $defaultProject = Project::first();

        if ($defaultProject) {
            TelegramSubscription::firstOrCreate(
                ['chat_id' => $chatId],
                [
                    'project_id' => $defaultProject->id,
                    'chat_type' => $chatType,
                    'chat_name' => $chatInfo['first_name'] ?? ($chatInfo['title'] ?? 'Unknown'),
                    'subscribed_events' => json_encode(['alerts']),
                    'is_active' => true,
                ]
            );
        }

        $lines = [
            "🤖 *Appswatch Bot* — Connected!",
            "",
            "📁 Project: " . ($defaultProject ? $defaultProject->name : 'None configured'),
            "",
            "*Available Commands:*",
            "📊 /status — Project health report",
            "📋 /exceptions — Latest unresolved exceptions",
            "✅ /resolve `<id>` — Resolve an exception",
            "🔇 /mute `<id>` `[hours]` — Mute an exception",
            "💾 /backup now — Trigger database backup",
            "📈 /uptime — Current uptime status",
            "📏 /metrics — Latest custom metrics",
            "📁 /projects — List all projects",
            "🔔 /subscribe — Subscribe to alerts",
            "🔕 /unsubscribe — Unsubscribe",
            "ℹ️ /help — This help message",
            "",
            "Use inline buttons below alert messages for quick actions.",
        ];

        $this->telegram->sendSimpleMessage($chatId, implode("\n", $lines));
    }

    protected function cmdStatus(string $chatId, array $args): void
    {
        $project = $this->getProjectForChat($chatId);
        if (!$project) {
            $this->telegram->sendSimpleMessage($chatId, '⚠️ No project configured. Use /start to set up.');
            return;
        }

        $exceptionsToday = Exception::where('project_id', $project->id)
            ->where('last_seen_at', '>=', now()->subDay())->count();
        $exceptionsNew = Exception::where('project_id', $project->id)
            ->where('last_seen_at', '>=', now()->subDay())
            ->where('status', 'unresolved')->count();

        $uptimeMetric = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'uptime')
            ->where('metric_name', 'uptime_percent')
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at', 'desc')->first();
        $uptimePct = $uptimeMetric ? round($uptimeMetric->metric_value, 2) : 0;

        $responseMetric = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'http_request')
            ->where('metric_name', 'avg_duration')
            ->where('recorded_at', '>=', now()->subHour())
            ->first();
        $avgResponseTime = $responseMetric ? round($responseMetric->metric_value) : 0;

        $queueFailures = DB::table('queue_jobs')
            ->where('project_id', $project->id)
            ->where('status', 'failed')
            ->where('finished_at', '>=', now()->subDay())
            ->count();

        $this->telegram->sendStatusReport($chatId, [
            'project_name' => $project->name,
            'exceptions_today' => $exceptionsToday,
            'exceptions_new' => $exceptionsNew,
            'uptime_pct' => $uptimePct,
            'avg_response_time' => $avgResponseTime,
            'queue_failures' => $queueFailures,
        ]);
    }

    protected function cmdExceptions(string $chatId, array $args): void
    {
        $project = $this->getProjectForChat($chatId);
        if (!$project) {
            $this->telegram->sendSimpleMessage($chatId, '⚠️ No project configured. Use /start to set up.');
            return;
        }

        $page = max(1, intval($args[0] ?? 1));
        $perPage = 5;
        $offset = ($page - 1) * $perPage;

        $total = Exception::where('project_id', $project->id)
            ->where('status', 'unresolved')->count();
        $totalPages = max(1, (int)ceil($total / $perPage));

        $exceptions = Exception::where('project_id', $project->id)
            ->where('status', 'unresolved')
            ->orderBy('last_seen_at', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'class' => class_basename($e->class),
                    'message' => \Illuminate\Support\Str::limit($e->message, 100),
                    'severity' => $e->severity,
                    'occurrence_count' => $e->occurrence_count,
                    'first_seen' => $e->first_seen_at ? $e->first_seen_at->diffForHumans() : 'N/A',
                    'last_seen' => $e->last_seen_at ? $e->last_seen_at->diffForHumans() : 'N/A',
                ];
            })
            ->toArray();

        $this->telegram->sendExceptionList($chatId, $exceptions, $page, $totalPages);
    }

    protected function cmdResolve(string $chatId, array $args): void
    {
        $exceptionId = $args[0] ?? '';
        if (empty($exceptionId)) {
            $this->telegram->sendSimpleMessage($chatId, 'Usage: /resolve `<exception_id>`');
            return;
        }

        $exception = Exception::find($exceptionId);
        if (!$exception) {
            $this->telegram->sendSimpleMessage($chatId, '❌ Exception not found.');
            return;
        }

        $exception->update(['status' => 'resolved']);
        $this->telegram->sendSimpleMessage($chatId, "✅ Exception *{$exception->id}* has been resolved.");
    }

    protected function cmdMute(string $chatId, array $args): void
    {
        $exceptionId = $args[0] ?? '';
        $hours = intval($args[1] ?? 1);

        if (empty($exceptionId)) {
            $this->telegram->sendSimpleMessage($chatId, 'Usage: /mute `<exception_id>` `[hours]`');
            return;
        }

        $exception = Exception::find($exceptionId);
        if (!$exception) {
            $this->telegram->sendSimpleMessage($chatId, '❌ Exception not found.');
            return;
        }

        $exception->update(['status' => 'muted']);
        $this->telegram->sendSimpleMessage($chatId, "🔇 Exception *{$exception->id}* muted for {$hours}h.");
    }

    protected function cmdBackup(string $chatId, array $args): void
    {
        $project = $this->getProjectForChat($chatId);
        if (!$project) {
            $this->telegram->sendSimpleMessage($chatId, '⚠️ No project configured.');
            return;
        }

        // Dispatch the backup command via queue
        \Illuminate\Support\Facades\Artisan::queue('appswatch:integrations:run-backups');

        $this->telegram->sendSimpleMessage($chatId, "💾 Database backup initiated for *{$project->name}*.\n\nYou'll be notified when it completes.");
    }

    protected function cmdUptime(string $chatId, array $args): void
    {
        $project = $this->getProjectForChat($chatId);
        if (!$project) {
            $this->telegram->sendSimpleMessage($chatId, '⚠️ No project configured.');
            return;
        }

        $checks = IntegrationMetric::where('project_id', $project->id)
            ->where('integration', 'uptime')
            ->where('metric_name', 'response_time_ms')
            ->where('recorded_at', '>=', now()->subHour())
            ->orderBy('recorded_at', 'desc')
            ->take(10)
            ->get();

        if ($checks->isEmpty()) {
            $this->telegram->sendSimpleMessage($chatId, '📈 No uptime checks recorded in the last hour.');
            return;
        }

        $lines = ["📈 *{$project->name}* — Recent Uptime Checks", ''];

        foreach ($checks as $check) {
            $url = $check->dimensions['url'] ?? 'unknown';
            $statusEmoji = $check->dimensions['status'] === 'up' ? '🟢' : '🔴';
            $lines[] = "{$statusEmoji} `{$url}` — {$check->metric_value}ms";
            $lines[] = "   " . $check->recorded_at->diffForHumans();
            $lines[] = '';
        }

        $this->telegram->sendSimpleMessage($chatId, implode("\n", $lines));
    }

    protected function cmdMetrics(string $chatId, array $args): void
    {
        $project = $this->getProjectForChat($chatId);
        if (!$project) {
            $this->telegram->sendSimpleMessage($chatId, '⚠️ No project configured.');
            return;
        }

        $metrics = IntegrationMetric::where('project_id', $project->id)
            ->whereNotIn('integration', ['uptime', 'server_monitor', 'ssl_check', 'domain_expiry', 'database_backup', 'service_vitals', 'anomaly_detection'])
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at', 'desc')
            ->take(15)
            ->get();

        if ($metrics->isEmpty()) {
            $this->telegram->sendSimpleMessage($chatId, '📏 No custom metrics recorded in the last 24 hours.');
            return;
        }

        $byName = $metrics->groupBy('metric_name');
        $lines = ["📏 *{$project->name}* — Latest Metrics", ''];

        foreach ($byName as $name => $group) {
            $latest = $group->first();
            $unit = $latest->unit ? " {$latest->unit}" : '';
            $lines[] = "• *{$name}*: {$latest->metric_value}{$unit}";
            $lines[] = "   " . $latest->recorded_at->diffForHumans();
            $lines[] = '';
        }

        $this->telegram->sendSimpleMessage($chatId, implode("\n", $lines));
    }

    protected function cmdHelp(string $chatId): void
    {
        $this->cmdStart($chatId, 'private', ['first_name' => 'User']);
    }

    protected function cmdSubscribe(string $chatId, array $args): void
    {
        $subscription = TelegramSubscription::where('chat_id', $chatId)->first();

        if (!$subscription) {
            $this->telegram->sendSimpleMessage($chatId, 'Use /start first to connect to a project.');
            return;
        }

        $events = json_decode($subscription->subscribed_events ?? '[]', true);
        $events[] = 'alerts';
        $events = array_unique($events);
        $subscription->update(['subscribed_events' => json_encode($events), 'is_active' => true]);

        $this->telegram->sendSimpleMessage($chatId, "🔔 Subscribed to alerts for *{$subscription->project->name}*.\n\nUse /unsubscribe to stop receiving alerts.");
    }

    protected function cmdUnsubscribe(string $chatId): void
    {
        $subscription = TelegramSubscription::where('chat_id', $chatId)->first();

        if ($subscription) {
            $subscription->update(['is_active' => false]);
            $this->telegram->sendSimpleMessage($chatId, '🔕 Unsubscribed. You will no longer receive alert notifications.\n\nUse /subscribe to re-enable.');
        } else {
            $this->telegram->sendSimpleMessage($chatId, 'No active subscription found.');
        }
    }

    protected function cmdProjects(string $chatId): void
    {
        $projects = Project::where('is_active', true)->get();

        if ($projects->isEmpty()) {
            $this->telegram->sendSimpleMessage($chatId, 'No active projects found.');
            return;
        }

        $lines = ['📁 *Projects*', ''];

        foreach ($projects as $project) {
            $exceptionCount = Exception::where('project_id', $project->id)
                ->where('status', 'unresolved')->count();
            $lines[] = "• *{$project->name}* ({$project->environment})";
            $lines[] = "  {$exceptionCount} unresolved exceptions";
            $lines[] = '';
        }

        $this->telegram->sendSimpleMessage($chatId, implode("\n", $lines));
    }

    // ─── Callback Handlers ─────────────────────────────────────────

    protected function callbackResolve(string $chatId, string $exceptionId): void
    {
        $exception = Exception::find($exceptionId);
        if ($exception) {
            $exception->update(['status' => 'resolved']);
            $this->telegram->sendSimpleMessage($chatId, "✅ Exception *{$exception->id}* resolved.");
        }
    }

    protected function callbackMute(string $chatId, string $exceptionId, int $hours): void
    {
        $exception = Exception::find($exceptionId);
        if ($exception) {
            $exception->update(['status' => 'muted']);
            $this->telegram->sendSimpleMessage($chatId, "🔇 Exception *{$exception->id}* muted for {$hours}h.");
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /**
     * Get the project associated with a Telegram chat.
     */
    protected function getProjectForChat(string $chatId): ?Project
    {
        $subscription = TelegramSubscription::where('chat_id', $chatId)
            ->where('is_active', true)
            ->first();

        if ($subscription) {
            return $subscription->project;
        }

        // Fall back to default chat ID or first project
        $defaultChatId = config('services.telegram.default_chat_id');
        if ($chatId === $defaultChatId) {
            return Project::first();
        }

        return Project::first();
    }
}