<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppException;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExceptionsController extends Controller
{
    public function index(Request $request)
    {
        // Default: show all projects' exceptions (for multi-project dashboard)
        // In MVP, we assume user has at least one project
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        // Sorting
        $sort = $request->input('sort', 'occurrence_count');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['occurrence_count', 'last_seen_at', 'class', 'severity', 'status'];

        if (!in_array($sort, $allowedSorts)) {
            $sort = 'occurrence_count';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query = AppException::where('project_id', $project->id)
            ->orderBy($sort, $direction);

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('class', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%");
            });
        }

        $exceptions = $query->paginate(25);
        $projects = Project::where('is_active', true)->get();

        // Stats for cards — cached per project for 2 minutes to avoid 4 COUNT queries per page load
        $stats = cache()->remember('exceptions:stats:' . $project->id, 120, function () use ($project) {
            $base = AppException::where('project_id', $project->id);
            $since = now()->subDay();

            return [
                'totalExceptions' => (clone $base)->count(),
                'unresolvedCount' => (clone $base)->where('status', 'unresolved')->count(),
                'criticalCount' => (clone $base)->where('severity', 'critical')->count(),
                'newTodayCount' => (clone $base)->where('first_seen_at', '>=', $since)->count(),
            ];
        });

        return view('exceptions.index', array_merge(compact(
            'exceptions', 'projects', 'project',
        ), $stats));
    }

    public function show(string $id)
    {
        $exception = AppException::with('project')->findOrFail($id);
        $project = $exception->project;

        // Find similar exceptions (same fingerprint group is already grouped,
        // so find ones with similar class or file)
        $similar = AppException::where('project_id', $project->id)
            ->where('id', '!=', $exception->id)
            ->where(function ($q) use ($exception) {
                $q->where('class', $exception->class)
                  ->orWhere('file', $exception->file);
            })
            ->limit(5)
            ->get();

        // Occurrence history for sparkline chart
        $occurrenceHistory = DB::table('exceptions')
            ->where('fingerprint', $exception->fingerprint)
            ->where('project_id', $exception->project_id)
            ->selectRaw('DATE(last_seen_at) as date, SUM(occurrence_count) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

        $chartLabels = $occurrenceHistory->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M j'))->toArray();
        $chartData = $occurrenceHistory->pluck('count')->toArray();

        return view('exceptions.show', compact('exception', 'project', 'similar', 'chartLabels', 'chartData'));
    }

    public function updateStatus(Request $request, string $id)
    {
        $exception = AppException::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:unresolved,resolved,ignored,muted',
        ]);

        $exception->update(['status' => $validated['status']]);

        return back()->with('success', 'Exception status updated.');
    }
}