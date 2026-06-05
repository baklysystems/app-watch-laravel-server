<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\QueueJob;
use Illuminate\Http\Request;

class QueuesController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $query = QueueJob::where('project_id', $project->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('queue')) {
            $query->where('queue', $request->input('queue'));
        }

        $jobs = $query->paginate(25);
        $projects = Project::where('is_active', true)->get();

        // Stats
        $pending = QueueJob::where('project_id', $project->id)->where('status', 'pending')->count();
        $processing = QueueJob::where('project_id', $project->id)->where('status', 'processing')->count();
        $failed = QueueJob::where('project_id', $project->id)->where('status', 'failed')->count();
        $completed = QueueJob::where('project_id', $project->id)->where('status', 'completed')->count();

        return view('queues.index', compact('jobs', 'project', 'projects', 'pending', 'processing', 'failed', 'completed'));
    }

    public function show(string $id)
    {
        $job = QueueJob::with('project', 'exception')->findOrFail($id);
        $project = $job->project;

        return view('queues.show', compact('job', 'project'));
    }
}