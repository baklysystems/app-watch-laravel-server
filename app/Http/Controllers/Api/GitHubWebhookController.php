<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Integrations\GitHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        // Verify X-Hub-Signature-256
        $projectId = $request->query('project_id') ?? $request->header('X-Project-Id');
        if (!$projectId) {
            return $this->findProjectByRepo($request);
        }

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        return $this->processWebhook($request, $project);
    }

    protected function findProjectByRepo(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $repoFullName = $payload['repository']['full_name'] ?? null;

        if (!$repoFullName) {
            return response()->json(['error' => 'Cannot identify project'], 400);
        }

        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->github->enabled', true)
            ->get();

        foreach ($projects as $project) {
            $config = $project->integrations_config['github'] ?? [];
            if (($config['repository'] ?? '') === $repoFullName) {
                return $this->processWebhook($request, $project);
            }
        }

        return response()->json(['error' => 'No matching project'], 404);
    }

    protected function processWebhook(Request $request, Project $project): \Illuminate\Http\JsonResponse
    {
        $eventType = $request->header('X-GitHub-Event');
        $payload = json_decode($request->getContent(), true);

        if (!$eventType) {
            return response()->json(['error' => 'Missing X-GitHub-Event header'], 400);
        }

        // Verify signature
        $config = $project->integrations_config['github'] ?? [];
        $webhookSecret = $config['webhook_secret'] ?? null;

        if ($webhookSecret) {
            $signature = $request->header('X-Hub-Signature-256');
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $webhookSecret);

            if (!$signature || !hash_equals($expectedSignature, $signature)) {
                Log::warning("GitHub Webhook: Invalid signature for project {$project->id}");
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        try {
            $service = app(GitHubService::class);
            $service->handleWebhook($project, $eventType, $payload);

            Log::info("GitHub Webhook: Processed {$eventType} for {$project->name}");
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::warning("GitHub Webhook: Failed processing {$eventType} — {$e->getMessage()}");
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}