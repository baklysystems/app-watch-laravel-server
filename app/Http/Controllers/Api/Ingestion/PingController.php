<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class PingController extends Controller
{
    /**
     * A minimal ping endpoint that the client calls to verify:
     * 1. The API key is valid
     * 2. The project is active
     * 3. The server is reachable
     * 4. Sync is working end-to-end
     *
     * Returns a rich status payload that the client/developer can inspect
     * to confirm everything is configured correctly.
     */
    public function __invoke(Request $request)
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        // The middleware already validates the API key, project active status,
        // and rate limits. If we reach here, auth is successful.

        // Build a comprehensive sync-check response
        $apiKey = $project->apiKeys()->where('key_prefix', substr($request->bearerToken(), 0, 8))->first();

        // Compute data freshness for each telemetry type
        $dataFreshness = [
            'exceptions' => $this->latestTimestamp($project->exceptions(), 'last_seen_at'),
            'logs'       => $this->latestTimestamp($project->logEntries(), 'occurred_at'),
            'queues'     => $this->latestTimestamp($project->queueJobs(), 'created_at'),
            'queries'    => $this->latestTimestamp($project->databaseQueries(), 'occurred_at'),
            'requests'   => $this->latestTimestamp($project->httpRequests(), 'occurred_at'),
            'schedules'  => $this->latestTimestamp($project->scheduledTasks(), 'created_at'),
            'metrics'    => $this->latestTimestamp($project->metrics(), 'recorded_at'),
        ];

        $volumes = [
            'exceptions' => $project->exceptions()->count(),
            'log_entries' => $project->logEntries()->count(),
            'queue_jobs' => $project->queueJobs()->count(),
            'http_requests' => $project->httpRequests()->count(),
            'scheduled_tasks' => $project->scheduledTasks()->count(),
            'metrics' => $project->metrics()->count(),
        ];

        return response()->json([
            'status'  => 'connected',
            'message' => 'Sync is working. Project is active and receiving data.',
            'project' => [
                'name'         => $project->name,
                'slug'         => $project->slug,
                'environment'  => $project->environment,
                'last_seen_at' => $project->last_seen_at?->toIso8601String(),
            ],
            'auth' => [
                'api_key_prefix'   => $apiKey?->key_prefix ?? 'unknown',
                'api_key_name'     => $apiKey?->name ?? 'unknown',
                'api_key_last_used_at' => $apiKey?->last_used_at?->toIso8601String(),
                'rate_limit'       => $project->rate_limit,
            ],
            'config' => [
                'is_active'       => $project->is_active,
                'retention_days'  => $project->retention_days,
            ],
            'dalat_freshness' => $dataFreshness,
            'data_volumes'    => $volumes,
            'sync_check' => [
                'server_time'       => now()->toIso8601String(),
                'server_timezone'   => config('app.timezone'),
                'recommendation'    => $this->getRecommendation($project, $dataFreshness),
            ],
        ]);
    }

    /**
     * Get the latest timestamp for a given relationship, or null if no records.
     */
    protected function latestTimestamp($query, string $column): ?string
    {
        $row = $query->orderBy($column, 'desc')->first();
        return $row?->{$column}?->toIso8601String();
    }

    /**
     * Provide a human-readable recommendation based on sync state.
     */
    protected function getRecommendation(Project $project, array $freshness): string
    {
        if (!$project->last_seen_at) {
            return 'No ping or data received yet. Ensure the client package is installed and configured with the correct API key and server URL.';
        }

        $ago = $project->last_seen_at->diffInMinutes(now());

        if ($ago > 30) {
            return "Last contact was {$ago} minutes ago. The client may be down or disconnected. Check the client app's scheduler (appswatch:flush-buffer) is running every minute.";
        }

        if ($ago > 5) {
            return "Last contact was {$ago} minutes ago. The client flush buffer may be delayed. Verify the scheduler is running.";
        }

        $hasData = collect($freshness)->filter()->isNotEmpty();

        if (!$hasData) {
            return 'Connected, but no telemetry data has been received yet. Data should arrive within the next minute as collectors run.';
        }

        return 'Everything looks good! The client is connected and sending data.';
    }
}