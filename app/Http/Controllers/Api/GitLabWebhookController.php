<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Integrations\GitLabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitLabWebhookController extends Controller
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        // Verify X-Gitlab-Token
        $payload = json_decode($request->getContent(), true);
        $projectGitlabId = $payload['project']['id'] ?? null;

        if (!$projectGitlabId) {
            return response()->json(['error' => 'Cannot identify GitLab project'], 400);
        }

        // Find matching Appswatch project
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->gitlab->enabled', true)
            ->get();

        $matchedProject = null;
        foreach ($projects as $project) {
            $config = $project->integrations_config['gitlab'] ?? [];
            if (($config['project_id'] ?? '') == $projectGitlabId) {
                $matchedProject = $project;
                break;
            }
        }

        if (!$matchedProject) {
            return response()->json(['error' => 'No matching project'], 404);
        }

        // Verify token
        $config = $matchedProject->integrations_config['gitlab'] ?? [];
        $expectedToken = $config['webhook_token'] ?? null;

        if ($expectedToken) {
            $providedToken = $request->header('X-Gitlab-Token');
            if (!$providedToken || !hash_equals($expectedToken, $providedToken)) {
                Log::warning("GitLab Webhook: Invalid token for project {$matchedProject->id}");
                return response()->json(['error' => 'Invalid token'], 403);
            }
        }

        $eventType = $request->header('X-Gitlab-Event') ?: 'Unknown';

        try {
            $service = app(GitLabService::class);
            $service->handleWebhook($matchedProject, $eventType, $payload);

            Log::info("GitLab Webhook: Processed {$eventType} for {$matchedProject->name}");
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::warning("GitLab Webhook: Failed processing {$eventType} — {$e->getMessage()}");
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}