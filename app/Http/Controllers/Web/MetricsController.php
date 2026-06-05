<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationMetric;
use App\Models\Metric;
use App\Models\Project;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        // Custom app metrics
        $customMetrics = Metric::where('project_id', $project->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(200)
            ->get()
            ->groupBy('name');

        // Integration metrics (uptime, server, SSL, etc.)
        $integrationMetrics = IntegrationMetric::where('project_id', $project->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(200)
            ->get()
            ->groupBy('integration');

        $projects = Project::where('is_active', true)->get();

        return view('metrics.index', compact('project', 'projects', 'customMetrics', 'integrationMetrics'));
    }
}