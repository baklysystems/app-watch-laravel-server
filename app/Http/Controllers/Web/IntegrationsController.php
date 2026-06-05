<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Http\Request;

class IntegrationsController extends Controller
{
    protected ?Project $project;
    protected array $projects;

    protected function resolveProject(Request $request): void
    {
        $projectId = $request->input('project_id', session('current_project_id'));

        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();

        if ($isSuperAdmin) {
            $this->projects = Project::where('is_active', true)->get()->toArray();
        } else {
            $this->projects = Project::where('is_active', true)
                ->where('user_id', $user->id)
                ->get()->toArray();
        }

        if ($projectId) {
            $this->project = Project::find($projectId);
        } elseif (!empty($this->projects)) {
            $this->project = Project::find($this->projects[0]['id']);
        } else {
            $this->project = null;
        }

        if ($this->project) {
            session(['current_project_id' => $this->project->id]);
        }
    }

    protected function getRecentMetrics(string $integration, ?string $metricName = null, int $limit = 50): array
    {
        if (!$this->project) return [];

        $query = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', $integration)
            ->orderBy('recorded_at', 'desc')
            ->limit($limit);

        if ($metricName) {
            $query->where('metric_name', $metricName);
        }

        return $query->get()->toArray();
    }

    protected function getConfig(string $key): array
    {
        if (!$this->project) return [];
        return $this->project->integrations_config[$key] ?? [];
    }

    protected function view(string $integrationName, string $title, array $extra = [])
    {
        return view('integrations.show', array_merge([
            'project' => $this->project,
            'projects' => $this->projects,
            'integration' => $integrationName,
            'title' => $title,
            'config' => $this->getConfig($integrationName),
            'metrics' => $this->getRecentMetrics($integrationName),
        ], $extra));
    }

    // ============ Google Analytics ============
    public function googleAnalytics(Request $request)
    {
        $this->resolveProject($request);
        if (!$this->project) return redirect()->route('dashboard');

        $metrics = $this->getRecentMetrics('google_analytics');

        // Fetch page_views and active_users in a single query via whereIn
        $specializedMetrics = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', 'google_analytics')
            ->whereIn('metric_name', ['page_views', 'active_users'])
            ->orderBy('recorded_at', 'desc')
            ->limit(60)
            ->get();

        $pageViews = $specializedMetrics->where('metric_name', 'page_views')->take(30)->values()->toArray();
        $activeUsers = $specializedMetrics->where('metric_name', 'active_users')->take(30)->values()->toArray();

        return $this->view('google_analytics', 'Google Analytics', [
            'pageViews' => $pageViews,
            'activeUsers' => $activeUsers,
        ]);
    }

    // ============ Google Search Console ============
    public function googleSearchConsole(Request $request)
    {
        $this->resolveProject($request);
        if (!$this->project) return redirect()->route('dashboard');

        $clicks = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', 'google_search_console')
            ->where('metric_name', 'clicks')
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->toArray();
        $impressions = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', 'google_search_console')
            ->where('metric_name', 'impressions')
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->toArray();

        return $this->view('google_search_console', 'Google Search Console', [
            'clicks' => $clicks,
            'impressions' => $impressions,
        ]);
    }

    // ============ Cloudflare ============
    public function cloudflare(Request $request)
    {
        $this->resolveProject($request);
        if (!$this->project) return redirect()->route('dashboard');

        return $this->view('cloudflare', 'Cloudflare Analytics');
    }

    // ============ Microsoft Clarity ============
    public function microsoftClarity(Request $request)
    {
        $this->resolveProject($request);
        if (!$this->project) return redirect()->route('dashboard');

        return $this->view('microsoft_clarity', 'Microsoft Clarity');
    }

    // ============ Stripe ============
    public function stripe(Request $request)
    {
        $this->resolveProject($request);
        if (!$this->project) return redirect()->route('dashboard');

        $mrr = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', 'stripe')
            ->where('metric_name', 'mrr')
            ->orderBy('recorded_at', 'desc')
            ->limit(30)
            ->get()
            ->toArray();

        return $this->view('stripe', 'Stripe', [
            'mrr' => $mrr,
        ]);
    }

    // ============ GitHub / GitLab ============
    public function github(Request $request)
    {
        $this->resolveProject($request);
        if (!$this->project) return redirect()->route('dashboard');

        $provider = $this->getConfig('github')['provider'] ?? 'github';
        $title = $provider === 'gitlab' ? 'GitLab' : 'GitHub';

        $deployments = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', 'github')
            ->where('metric_name', 'deployment')
            ->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $workflows = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', 'github')
            ->where('metric_name', 'workflow_run')
            ->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $releases = IntegrationMetric::where('project_id', $this->project->id)
            ->where('integration', 'github')
            ->where('metric_name', 'release')
            ->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        return $this->view('github', $title, [
            'provider' => $provider,
            'deployments' => $deployments,
            'workflows' => $workflows,
            'releases' => $releases,
        ]);
    }

    // ============ Email Services ============
    public function email(Request $request)
    {
        $this->resolveProject($request);
        if (!$this->project) return redirect()->route('dashboard');

        $cfg = $this->getConfig('email_service');
        $provider = $cfg['provider'] ?? 'mailgun';
        $providerLabel = match ($provider) {
            'postmark' => 'Postmark',
            'ses' => 'Amazon SES',
            default => 'Mailgun',
        };

        return $this->view('email_service', $providerLabel . ' Email Metrics', [
            'emailProvider' => $provider,
            'providerLabel' => $providerLabel,
        ]);
    }
}