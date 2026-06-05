<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ScheduledTask;
use Illuminate\Http\Request;

class SchedulesController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $query = ScheduledTask::where('project_id', $project->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('command')) {
            $query->where('command', 'like', '%' . $request->input('command') . '%');
        }

        $tasks = $query->paginate(25);
        $projects = Project::where('is_active', true)->get();

        $started = ScheduledTask::where('project_id', $project->id)->where('status', 'started')->count();
        $failed = ScheduledTask::where('project_id', $project->id)->where('status', 'failed')->count();
        $completed = ScheduledTask::where('project_id', $project->id)->where('status', 'completed')->count();

        return view('schedules.index', compact('tasks', 'project', 'projects', 'started', 'failed', 'completed'));
    }
}