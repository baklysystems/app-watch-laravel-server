<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $query = AuditLog::where('project_id', $projectId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // CSV Export
        if ($request->has('export')) {
            $logs = $query->limit(5000)->get();
            $csv = "Timestamp,User,Action,Entity Type,Entity ID,IP Address\n";
            foreach ($logs as $log) {
                $user = $log->user?->name ?? 'System';
                $csv .= "\"{$log->created_at}\",\"{$user}\",\"{$log->action}\",\"{$log->entity_type}\",\"{$log->entity_id}\",\"{$log->ip_address}\"\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="audit-log.csv"',
            ]);
        }

        $auditLogs = $query->paginate($request->input('per_page', 50));

        $entityTypes = AuditLog::where('project_id', $projectId)
            ->distinct()
            ->pluck('entity_type');

        return view('audit.index', compact('project', 'auditLogs', 'entityTypes'));
    }
}