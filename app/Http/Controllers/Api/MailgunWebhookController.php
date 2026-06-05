<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailgunWebhookController extends Controller
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $eventData = $request->input('event-data', []);
        $event = $eventData['event'] ?? null;
        $domain = $eventData['message']['headers']['from'] ?? null;

        if (!$event) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Find project by domain
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->mail_provider->enabled', true)
            ->get();

        $matchedProject = null;
        foreach ($projects as $project) {
            $config = $project->integrations_config['mail_provider'] ?? [];
            $configDomain = $config['domain'] ?? null;
            if ($configDomain && str_contains($eventData['message']['headers']['to'] ?? '', $configDomain)) {
                $matchedProject = $project;
                break;
            }
        }

        if (!$matchedProject) {
            return response()->json(['status' => 'ok'], 200);
        }

        $now = now();
        $metricName = match ($event) {
            'delivered' => 'delivered',
            'failed' => match ($eventData['severity'] ?? '') {
                'permanent' => 'failed_permanent',
                'temporary' => 'failed_temporary',
                default => 'total_failed',
            },
            'opened' => 'opened',
            'clicked' => 'clicked',
            'complained' => 'complained',
            'unsubscribed' => 'unsubscribed',
            default => null,
        };

        if ($metricName) {
            // Determine integration name based on provider
            $provider = $matchedProject->integrations_config['mail_provider']['provider'] ?? 'mailgun';

            IntegrationMetric::create([
                'project_id'   => $matchedProject->id,
                'integration'  => $provider,
                'metric_name'  => $metricName,
                'metric_value' => 1,
                'unit'         => 'count',
                'dimensions'   => ['event' => $event, 'domain' => $domain],
                'recorded_at'  => $now,
            ]);
        }

        Log::info("Mailgun Webhook: Processed {$event} for {$matchedProject->name}");
        return response()->json(['status' => 'ok'], 200);
    }
}