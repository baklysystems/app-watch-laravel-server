<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Project;
use App\Services\ServiceVitalsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::with('apiKeys')->findOrFail($projectId);
        $projects = Project::where('is_active', true)->get();

        return view('settings.index', compact('project', 'projects'));
    }

    public function updateProject(Request $request, string $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'retention_days' => 'nullable|integer|min:1|max:365',
            'rate_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $project->update($validated);

        return back()->with('success', 'Project settings updated.');
    }

    public function generateApiKey(string $projectId)
    {
        $project = Project::findOrFail($projectId);

        $apiKey = 'asw_' . Str::random(48);
        $hashed = password_hash($apiKey, PASSWORD_BCRYPT);

        $keyModel = ApiKey::create([
            'project_id' => $project->id,
            'key' => $hashed,
            'key_prefix' => substr($apiKey, 0, 8),
            'name' => 'Key ' . ($project->apiKeys()->count() + 1),
        ]);

        return back()->with('success', "New API key generated: {$apiKey} (copy it now — it won't be shown again)");
    }

    public function deleteApiKey(string $id)
    {
        $key = ApiKey::findOrFail($id);
        $key->delete();

        return back()->with('success', 'API key revoked successfully.');
    }

    public function deleteProject(string $id)
    {
        $project = Project::findOrFail($id);

        // Delete all related data
        $project->exceptions()->delete();
        $project->logEntries()->delete();
        $project->queueJobs()->delete();
        $project->databaseQueries()->delete();
        $project->httpRequests()->delete();
        $project->scheduledTasks()->delete();
        $project->metrics()->delete();
        $project->alerts()->delete();
        $project->integrationMetrics()->delete();
        $project->apiKeys()->delete();
        $project->delete();

        // Clear session if this was the current project
        if (session('current_project_id') == $id) {
            session()->forget('current_project_id');
        }

        return redirect()->route('dashboard')->with('success', 'Project deleted successfully.');
    }

    public function updateIntegrations(Request $request, string $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'integrations' => 'required|array',
            'integrations.uptime' => 'array',
            'integrations.uptime.enabled' => 'boolean',
            'integrations.uptime.url' => 'nullable|url|max:500',
            'integrations.ssl_check' => 'array',
            'integrations.ssl_check.enabled' => 'boolean',
            'integrations.ssl_check.domain' => 'nullable|string|max:255',
            'integrations.domain_expiry' => 'array',
            'integrations.domain_expiry.enabled' => 'boolean',
            'integrations.domain_expiry.domain' => 'nullable|string|max:255',
            'integrations.server_monitor' => 'array',
            'integrations.server_monitor.enabled' => 'boolean',
            'integrations.database_backup' => 'array',
            'integrations.database_backup.enabled' => 'boolean',
            'integrations.database_backup.retention_days' => 'nullable|integer|min:1|max:365',
            'integrations.mysql_health' => 'array',
            'integrations.mysql_health.enabled' => 'boolean',
            'integrations.mysql_health.host' => 'nullable|string|max:255',
            'integrations.mysql_health.port' => 'nullable|integer|min:1|max:65535',
            'integrations.mysql_health.user' => 'nullable|string|max:255',
            'integrations.mysql_health.password' => 'nullable|string|max:255',
            'integrations.service_vitals' => 'array',
            'integrations.service_vitals.enabled' => 'boolean',
            'integrations.log_retention' => 'array',
            'integrations.log_retention.days' => 'nullable|integer|min:1|max:365',
            'integrations.log_retention.max_size_mb' => 'nullable|integer|min:1|max:10000',
            'integrations.google_analytics' => 'array',
            'integrations.google_analytics.enabled' => 'boolean',
            'integrations.google_analytics.measurement_id' => 'nullable|string|max:255',
            'integrations.google_analytics.property_id' => 'nullable|string|max:255',
            'integrations.google_analytics.api_secret' => 'nullable|string|max:255',
            'integrations.google_search_console' => 'array',
            'integrations.google_search_console.enabled' => 'boolean',
            'integrations.google_search_console.site_url' => 'nullable|url|max:500',
            'integrations.google_search_console.oauth_key' => 'nullable|string|max:5000',
            'integrations.cloudflare' => 'array',
            'integrations.cloudflare.enabled' => 'boolean',
            'integrations.cloudflare.api_token' => 'nullable|string|max:255',
            'integrations.cloudflare.zone_id' => 'nullable|string|max:255',
            'integrations.cloudflare.account_id' => 'nullable|string|max:255',
            'integrations.microsoft_clarity' => 'array',
            'integrations.microsoft_clarity.enabled' => 'boolean',
            'integrations.microsoft_clarity.project_id' => 'nullable|string|max:255',
            'integrations.stripe' => 'array',
            'integrations.stripe.enabled' => 'boolean',
            'integrations.stripe.secret_key' => 'nullable|string|max:255',
            'integrations.stripe.webhook_secret' => 'nullable|string|max:255',
            'integrations.stripe.account_id' => 'nullable|string|max:255',
            'integrations.github' => 'array',
            'integrations.github.enabled' => 'boolean',
            'integrations.github.provider' => 'nullable|in:github,gitlab',
            'integrations.github.access_token' => 'nullable|string|max:255',
            'integrations.github.repository' => 'nullable|string|max:255',
            'integrations.email_service' => 'array',
            'integrations.email_service.enabled' => 'boolean',
            'integrations.email_service.provider' => 'nullable|in:mailgun,postmark,ses',
            'integrations.email_service.api_key' => 'nullable|string|max:255',
            'integrations.email_service.domain' => 'nullable|string|max:255',
            'integrations.telegram' => 'array',
            'integrations.telegram.enabled' => 'boolean',
            'integrations.telegram.bot_token' => 'nullable|string|max:255',
            'integrations.telegram.default_chat_id' => 'nullable|string|max:255',
            'integrations.n8n' => 'array',
            'integrations.n8n.enabled' => 'boolean',
            'integrations.n8n.webhook_url' => 'nullable|url|max:500',
            'integrations.n8n.auth_header' => 'nullable|string|max:255',
            'integrations.ifttt' => 'array',
            'integrations.ifttt.enabled' => 'boolean',
            'integrations.ifttt.webhook_key' => 'nullable|string|max:255',
            'integrations.ifttt.event_name' => 'nullable|string|max:255',
        ]);

        // Merge with existing config to preserve future integrations
        $existing = $project->integrations_config ?? [];
        $project->update(['integrations_config' => array_merge($existing, $validated['integrations'])]);

        return back()->with('success', 'Integration settings updated.');
    }

    /**
     * Run a live test of all service vitals for a project.
     */
    public function testService(Request $request, string $id)
    {
        $project = Project::findOrFail($id);
        $service = new ServiceVitalsService();
        $results = $service->checkProject($project);

        return response()->json([
            'project' => $project->name,
            'services' => $results,
        ]);
    }

    /**
     * Proxy the sync test: call the internal ping endpoint for the given project
     * and return a comprehensive sync status snapshot. This is called from the
     * "Test Sync Now" button on the Settings page.
     */
    public function testSync(Request $request, string $id)
    {
        $project = Project::with('apiKeys')->findOrFail($id);

        $apiKey = $project->apiKeys()->first();

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'No API key found for this project. Generate an API key first.',
            ], 400);
        }

        // Call our own ping endpoint via internal dispatch to avoid HTTP overhead
        $controller = new \App\Http\Controllers\Api\Ingestion\PingController();

        // Simulate an authenticated API request by setting the project attribute
        $fakeRequest = \Illuminate\Http\Request::create('/api/ingest/ping', 'GET');
        $fakeRequest->attributes->set('project', $project);

        // Set the bearer token so the ping controller finds the API key name/prefix
        $fakeRequest->headers->set('Authorization', 'Bearer ' . $apiKey->key_prefix . 'placeholder');

        return $controller($fakeRequest);
    }

    public function createProject(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'environment' => 'required|in:production,staging,local',
        ]);

        $slug = Str::slug($validated['name']) . '-' . Str::random(6);
        $apiKey = 'asw_' . Str::random(48);
        $hashed = password_hash($apiKey, PASSWORD_BCRYPT);

        $project = Project::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'api_key' => $hashed,
            'api_key_prefix' => substr($apiKey, 0, 8),
            'environment' => $validated['environment'],
            'slug' => $slug,
            'retention_days' => 30,
            'rate_limit' => 600,
            'is_active' => true,
        ]);

        ApiKey::create([
            'project_id' => $project->id,
            'key' => $hashed,
            'key_prefix' => substr($apiKey, 0, 8),
            'name' => 'Default Key',
        ]);

        session(['current_project_id' => $project->id]);

        return redirect()->route('dashboard')->with('success', "Project created! Your API key: {$apiKey} (copy it now — it won't be shown again)");
    }
}