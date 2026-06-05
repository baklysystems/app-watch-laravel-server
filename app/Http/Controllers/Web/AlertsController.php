<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Project;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id', Project::where('is_active', true)->first()?->id));
        $project = Project::findOrFail($projectId);

        $alerts = Alert::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $projects = Project::where('is_active', true)->get();

        return view('alerts.index', compact('project', 'projects', 'alerts'));
    }

    public function create(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id', Project::where('is_active', true)->first()?->id));
        $project = Project::findOrFail($projectId);
        $projects = Project::where('is_active', true)->get();

        return view('alerts.create', compact('project', 'projects'));
    }

    public function store(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:exception_rate,log_level,queue_failure,query_slow,metric_threshold,mysql_connection_saturation,mysql_replication_lag,backup_stale,anomaly_detected',
            'conditions' => 'required|array',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:mail,slack,discord,webhook,telegram,n8n',
            'cooldown_minutes' => 'required|integer|min:1|max:1440',
        ]);

        $alert = Alert::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'conditions' => $validated['conditions'],
            'channels' => $validated['channels'],
            'cooldown_minutes' => $validated['cooldown_minutes'],
            'is_active' => true,
        ]);

        return redirect()->route('alerts.index', ['project_id' => $project->id])
            ->with('success', 'Alert rule created successfully.');
    }

    public function edit(string $id)
    {
        $alert = Alert::with('project')->findOrFail($id);
        $project = $alert->project;
        $projects = Project::where('is_active', true)->get();

        return view('alerts.edit', compact('alert', 'project', 'projects'));
    }

    public function update(Request $request, string $id)
    {
        $alert = Alert::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:exception_rate,log_level,queue_failure,query_slow,metric_threshold,mysql_connection_saturation,mysql_replication_lag,backup_stale',
            'conditions' => 'nullable|array',
            'channels' => 'nullable|array|min:1',
            'channels.*' => 'in:mail,slack,discord,webhook',
            'cooldown_minutes' => 'nullable|integer|min:1|max:1440',
            'is_active' => 'nullable|boolean',
        ]);

        $alert->update($validated);

        return redirect()->route('alerts.index', ['project_id' => $alert->project_id])
            ->with('success', 'Alert rule updated.');
    }

    public function toggle(string $id)
    {
        $alert = Alert::findOrFail($id);
        $alert->update(['is_active' => !$alert->is_active]);

        return back()->with('success', $alert->is_active ? 'Alert enabled.' : 'Alert disabled.');
    }

    public function destroy(string $id)
    {
        $alert = Alert::findOrFail($id);
        $projectId = $alert->project_id;
        $alert->delete();

        return redirect()->route('alerts.index', ['project_id' => $projectId])
            ->with('success', 'Alert rule deleted.');
    }
}