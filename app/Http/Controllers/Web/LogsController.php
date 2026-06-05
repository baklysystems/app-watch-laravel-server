<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LogEntry;
use App\Models\Project;
use Illuminate\Http\Request;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $query = LogEntry::where('project_id', $project->id)
            ->orderBy('occurred_at', 'desc');

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('message', 'like', "%{$search}%");
        }

        $logs = $query->paginate(50);
        $projects = Project::where('is_active', true)->get();

        return view('logs.index', compact('logs', 'project', 'projects'));
    }

    public function show(string $id)
    {
        $log = LogEntry::with('project')->findOrFail($id);
        $project = $log->project;

        // Show logs from the same batch
        $related = collect();
        if ($log->batch_id) {
            $related = LogEntry::where('project_id', $project->id)
                ->where('batch_id', $log->batch_id)
                ->where('id', '!=', $log->id)
                ->orderBy('occurred_at')
                ->get();
        }

        return view('logs.show', compact('log', 'project', 'related'));
    }
}