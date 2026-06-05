<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exception;
use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrometheusMetricsController extends Controller
{
    /**
     * Expose metrics in Prometheus text format.
     * Authenticated via API key query param or Bearer token.
     */
    public function __invoke(Request $request): \Illuminate\Http\Response
    {
        // Authentication
        $prometheusKey = config('services.prometheus.api_key');
        if ($prometheusKey) {
            $providedKey = $request->query('api_key') ?? $request->bearerToken();
            if (!$providedKey || !hash_equals($prometheusKey, $providedKey)) {
                return response('Unauthorized', 401);
            }
        }

        $output = [];

        // Exceptions by project and status
        $exceptionStats = DB::table('exceptions')
            ->select('project_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('project_id', 'status')
            ->get();

        $projectNames = Project::pluck('name', 'id');

        $output[] = '# HELP appswatch_exceptions_total Total exceptions by project and status';
        $output[] = '# TYPE appswatch_exceptions_total gauge';

        foreach ($exceptionStats as $stat) {
            $projectName = $projectNames[$stat->project_id] ?? 'unknown';
            $safeProject = preg_replace('/[^a-zA-Z0-9_]/', '_', $projectName);
            $output[] = sprintf(
                'appswatch_exceptions_total{project="%s",status="%s"} %d',
                $safeProject, $stat->status, $stat->total
            );
        }

        // Uptime percentage (last 24h)
        $uptimeMetrics = IntegrationMetric::where('integration', 'uptime')
            ->where('metric_name', 'uptime_pct')
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at', 'desc')
            ->limit(200)
            ->get();

        $output[] = '# HELP appswatch_uptime_percent Uptime percentage last 24h';
        $output[] = '# TYPE appswatch_uptime_percent gauge';

        $latestUptime = [];
        foreach ($uptimeMetrics as $metric) {
            $key = $metric->project_id . '-' . ($metric->dimensions['url'] ?? 'default');
            if (!isset($latestUptime[$key])) {
                $latestUptime[$key] = $metric;
            }
        }

        foreach ($latestUptime as $metric) {
            $projectName = $projectNames[$metric->project_id] ?? 'unknown';
            $safeProject = preg_replace('/[^a-zA-Z0-9_]/', '_', $projectName);
            $url = $metric->dimensions['url'] ?? 'default';
            $safeUrl = preg_replace('/[^a-zA-Z0-9_:\/.\-]/', '_', $url);
            $output[] = sprintf(
                'appswatch_uptime_percent{project="%s",url="%s"} %.2f',
                $safeProject, $safeUrl, $metric->metric_value
            );
        }

        // Average response time (last hour)
        $responseMetrics = IntegrationMetric::where('integration', 'uptime')
            ->where('metric_name', 'response_time_ms')
            ->where('recorded_at', '>=', now()->subHour())
            ->get();

        $output[] = '# HELP appswatch_avg_response_time_ms Average response time last hour';
        $output[] = '# TYPE appswatch_avg_response_time_ms gauge';

        $responseAvgs = [];
        foreach ($responseMetrics as $metric) {
            $pid = $metric->project_id;
            if (!isset($responseAvgs[$pid])) $responseAvgs[$pid] = ['sum' => 0, 'count' => 0];
            $responseAvgs[$pid]['sum'] += $metric->metric_value;
            $responseAvgs[$pid]['count']++;
        }

        foreach ($responseAvgs as $pid => $data) {
            $avg = $data['count'] > 0 ? round($data['sum'] / $data['count'], 1) : 0;
            $projectName = $projectNames[$pid] ?? 'unknown';
            $safeProject = preg_replace('/[^a-zA-Z0-9_]/', '_', $projectName);
            $output[] = sprintf(
                'appswatch_avg_response_time_ms{project="%s"} %.1f',
                $safeProject, $avg
            );
        }

        // Queue failures
        $queueFailures = DB::table('queued_jobs')
            ->select('project_id', 'queue', DB::raw('count(*) as total'))
            ->where('status', 'failed')
            ->groupBy('project_id', 'queue')
            ->get();

        $output[] = '# HELP appswatch_queue_failures_total Failed queue jobs by queue name';
        $output[] = '# TYPE appswatch_queue_failures_total counter';

        foreach ($queueFailures as $failure) {
            $projectName = $projectNames[$failure->project_id] ?? 'unknown';
            $safeProject = preg_replace('/[^a-zA-Z0-9_]/', '_', $projectName);
            $safeQueue = preg_replace('/[^a-zA-Z0-9_]/', '_', $failure->queue);
            $output[] = sprintf(
                'appswatch_queue_failures_total{project="%s",queue="%s"} %d',
                $safeProject, $safeQueue, $failure->total
            );
        }

        return response(implode("\n", $output) . "\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}