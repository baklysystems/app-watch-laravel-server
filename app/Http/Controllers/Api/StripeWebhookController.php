<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $eventType = $payload['type'] ?? null;

        if (!$eventType) {
            return response()->json(['error' => 'Missing event type'], 400);
        }

        // Find project (Stripe webhooks can include project_id as metadata or
        // we match by finding projects with Stripe enabled)
        $accountId = $payload['account'] ?? null;
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->stripe->enabled', true)
            ->get();

        $now = now();

        foreach ($projects as $project) {
            try {
                $this->processEvent($project, $eventType, $payload, $now);
            } catch (\Throwable $e) {
                Log::warning("Stripe Webhook: Failed processing {$eventType} for {$project->name} — {$e->getMessage()}");
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    protected function processEvent(Project $project, string $eventType, array $payload, $now): void
    {
        $data = $payload['data']['object'] ?? [];

        $metricName = match ($eventType) {
            'charge.succeeded' => 'charge_succeeded',
            'charge.failed' => 'charge_failed',
            'charge.refunded' => 'charge_refunded',
            'charge.dispute.created' => 'dispute_created',
            'charge.dispute.closed' => 'dispute_closed',
            'invoice.payment_succeeded' => 'payment_succeeded',
            'invoice.payment_failed' => 'payment_failed',
            'customer.subscription.created' => 'subscription_created',
            'customer.subscription.deleted' => 'subscription_deleted',
            default => null,
        };

        if ($metricName) {
            $amount = $data['amount'] ?? 0;
            $currency = $data['currency'] ?? 'usd';

            IntegrationMetric::create([
                'project_id'   => $project->id,
                'integration'  => 'stripe',
                'metric_name'  => $metricName,
                'metric_value' => $amount > 0 ? ($amount / 100) : 1, // Convert cents to dollars for charges
                'unit'         => $amount > 0 ? 'usd' : 'count',
                'dimensions'   => [
                    'event' => $eventType,
                    'currency' => $currency,
                    'charge_id' => $data['id'] ?? null,
                    'customer' => $data['customer'] ?? null,
                ],
                'recorded_at'  => $now,
            ]);

            Log::info("Stripe Webhook: Processed {$eventType} for {$project->name}");
        }
    }
}