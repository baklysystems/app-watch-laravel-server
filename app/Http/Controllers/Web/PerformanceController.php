<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DatabaseQuery;
use App\Models\HttpRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function queries(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $query = DatabaseQuery::where('project_id', $project->id)
            ->orderBy('duration_ms', 'desc');

        if ($request->filled('slow_only')) {
            $query->where('is_slow', true);
        }

        $queries = $query->limit(100)->paginate(50);
        $projects = Project::where('is_active', true)->get();

        $slowCount = DatabaseQuery::where('project_id', $project->id)->where('is_slow', true)->count();
        $avgDuration = DatabaseQuery::where('project_id', $project->id)->avg('duration_ms');

        return view('performance.queries', compact('queries', 'project', 'projects', 'slowCount', 'avgDuration'));
    }

    public function requests(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $query = HttpRequest::where('project_id', $project->id)
            ->orderBy('duration_ms', 'desc');

        $requests = $query->paginate(25);
        $projects = Project::where('is_active', true)->get();

        $avgDuration = HttpRequest::where('project_id', $project->id)->avg('duration_ms');
        $avgMemory = HttpRequest::where('project_id', $project->id)->avg('memory_usage_mb');

        return view('performance.requests', compact('requests', 'project', 'projects', 'avgDuration', 'avgMemory'));
    }
}