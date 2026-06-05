<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SavedFilter;
use Illuminate\Http\Request;

class SavedFilterController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $filters = SavedFilter::where('project_id', $projectId)
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($filters);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:exceptions,logs,queries,requests',
            'filters' => 'required|array',
            'is_default' => 'boolean',
        ]);

        $projectId = $request->input('project_id', session('current_project_id'));
        Project::findOrFail($projectId);

        // If setting as default, clear other defaults for this type
        if ($request->boolean('is_default')) {
            SavedFilter::where('project_id', $projectId)
                ->where('type', $request->type)
                ->update(['is_default' => false]);
        }

        $filter = SavedFilter::create([
            'project_id' => $projectId,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'type' => $request->type,
            'filters' => $request->filters,
            'is_default' => $request->boolean('is_default'),
        ]);

        return response()->json(['status' => 'ok', 'filter' => $filter], 201);
    }

    public function update(Request $request, SavedFilter $filter)
    {
        $request->validate([
            'name' => 'string|max:255',
            'filters' => 'array',
            'is_default' => 'boolean',
        ]);

        if ($request->has('is_default') && $request->boolean('is_default')) {
            SavedFilter::where('project_id', $filter->project_id)
                ->where('type', $filter->type)
                ->where('id', '!=', $filter->id)
                ->update(['is_default' => false]);
        }

        $filter->update($request->only(['name', 'filters', 'is_default']));

        return response()->json(['status' => 'ok', 'filter' => $filter]);
    }

    public function destroy(SavedFilter $filter)
    {
        $filter->delete();
        return response()->json(['status' => 'ok']);
    }
}