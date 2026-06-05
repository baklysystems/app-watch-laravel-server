<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppException;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function timeline(Request $request)
    {
        $projectId = $request->input('project_id', session('current_project_id'));
        $project = Project::findOrFail($projectId);

        $startDate = $request->input('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $selectedTypes = $request->input('types', ['exception', 'alert', 'deployment', 'uptime', 'backup']);

        $events = collect();

        if (in_array('exception', $selectedTypes)) {
            $exceptions = AppException::where('project_id', $projectId)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('last_seen_at', '>=', $startDate)
                      ->orWhere('created_at', '>=', $startDate);
                })
                ->where('last_seen_at', '<=', $endDate . ' 23:59:59')
                ->orderBy('last_seen_at', 'desc')
                ->limit(200)
                ->get()
                ->map(function ($e) {
                    return [
                        'type' => 'exception',
                        'severity' => mb_strtolower($e->severity ?? 'error'),
                        'timestamp' => $e->last_seen_at,
                        'title' => $e->class ?? 'Unknown Exception',
                        'summary' => \Illuminate\Support\Str::limit($e->message, 100),
                        'link' => route('exceptions.show', $e->id),
                        'project_id' => $e->project_id,
                    ];
                });
            $events = $events->concat($exceptions);
        }

        if (in_array('alert', $selectedTypes)) {
            $alerts = IntegrationMetric::where('project_id', $projectId)
                ->where('integration', 'alert')
                ->where('recorded_at', '>=', $startDate)
                ->where('recorded_at', '<=', $endDate . ' 23:59:59')
                ->orderBy('recorded_at', 'desc')
                ->limit(200)
                ->get()
                ->map(function ($a) {
                    return [
                        'type' => 'alert',
                        'severity' => $a->dimensions['severity'] ?? 'warning',
                        'timestamp' => $a->recorded_at,
                        'title' => $a->dimensions['alert_name'] ?? 'Alert Triggered',
                        'summary' => ($a->dimensions['details'] ?? '') ?: 'Alert conditions met',
                        'link' => '#',
                        'project_id' => $a->project_id,
                    ];
                });
            $events = $events->concat($alerts);
        }

        if (in_array('deployment', $selectedTypes)) {
            $deployments = IntegrationMetric::where('project_id', $projectId)
                ->whereIn('integration', ['github', 'gitlab'])
                ->where('metric_name', 'deployment')
                ->where('recorded_at', '>=', $startDate)
                ->where('recorded_at', '<=', $endDate . ' 23:59:59')
                ->orderBy('recorded_at', 'desc')
                ->limit(200)
                ->get()
                ->map(function ($d) {
                    return [
                        'type' => 'deployment',
                        'severity' => 'info',
                        'timestamp' => $d->recorded_at,
                        'title' => 'Deployment: ' . ($d->dimensions['ref'] ?? 'unknown'),
                        'summary' => ($d->dimensions['environment'] ?? '') . ' — ' . ($d->dimensions['status'] ?? 'unknown'),
                        'link' => '#',
                        'project_id' => $d->project_id,
                    ];
                });
            $events = $events->concat($deployments);
        }

        if (in_array('uptime', $selectedTypes)) {
            $uptimeEvents = IntegrationMetric::where('project_id', $projectId)
                ->where('integration', 'uptime')
                ->whereIn('metric_name', ['check_failed', 'check_recovered', 'uptime_pct'])
                ->where('recorded_at', '>=', $startDate)
                ->where('recorded_at', '<=', $endDate . ' 23:59:59')
                ->orderBy('recorded_at', 'desc')
                ->limit(200)
                ->get()
                ->map(function ($u) {
                    $isRecovery = $u->metric_name === 'check_recovered';
                    return [
                        'type' => 'uptime',
                        'severity' => $isRecovery ? 'success' : 'error',
                        'timestamp' => $u->recorded_at,
                        'title' => $isRecovery ? 'Uptime Recovered' : 'Uptime Check Failed',
                        'summary' => ($u->dimensions['url'] ?? '') ?: 'Health check status changed',
                        'link' => '#',
                        'project_id' => $u->project_id,
                    ];
                });
            $events = $events->concat($uptimeEvents);
        }

        if (in_array('backup', $selectedTypes)) {
            $backups = IntegrationMetric::where('project_id', $projectId)
                ->where('integration', 'backup')
                ->where('recorded_at', '>=', $startDate)
                ->where('recorded_at', '<=', $endDate . ' 23:59:59')
                ->orderBy('recorded_at', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($b) {
                    $status = $b->metric_name === 'completed' ? 'success' : 'error';
                    return [
                        'type' => 'backup',
                        'severity' => $status,
                        'timestamp' => $b->recorded_at,
                        'title' => 'Backup ' . $b->metric_name,
                        'summary' => ($b->dimensions['size'] ?? '') ? 'Size: ' . $b->dimensions['size'] : '',
                        'link' => '#',
                        'project_id' => $b->project_id,
                    ];
                });
            $events = $events->concat($backups);
        }

        $events = $events->sortByDesc('timestamp')->values();

        $groupedEvents = $events->groupBy(function ($event) {
            return \Carbon\Carbon::parse($event['timestamp'])->format('Y-m-d');
        });

        return view('incidents.timeline', compact('project', 'events', 'groupedEvents', 'startDate', 'endDate', 'selectedTypes'));
    }
}