<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): mixed
    {
        $apiKey = $request->bearerToken();

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key required. Use Authorization: Bearer {api_key}',
            ], 401);
        }

        // Find matching API key by prefix
        $prefix = substr($apiKey, 0, 8);

        $apiKeyModel = ApiKey::where('key_prefix', $prefix)->first();

        if (!$apiKeyModel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key.',
            ], 401);
        }

        // Verify hash
        if (!password_verify($apiKey, $apiKeyModel->key)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API key.',
            ], 401);
        }

        // Check project is active
        $project = $apiKeyModel->project;

        if (!$project || !$project->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project is inactive.',
            ], 403);
        }

        // Update last used at
        $apiKeyModel->update(['last_used_at' => now()]);

        // Rate limiting check
        $this->checkRateLimit($project);

        // Update project.last_seen_at on every successful API call
        $project->update(['last_seen_at' => now()]);

        // Share project with the request
        $request->attributes->set('project', $project);

        return $next($request);
    }

    protected function checkRateLimit($project): void
    {
        $limit = $project->rate_limit ?? 600;
        $key = "rate_limit:project:{$project->id}";

        $current = \Illuminate\Support\Facades\Cache::get($key, 0);

        if ($current >= $limit) {
            abort(429, 'Rate limit exceeded. Try again later.');
        }

        \Illuminate\Support\Facades\Cache::put($key, $current + 1, now()->addMinute());
    }
}