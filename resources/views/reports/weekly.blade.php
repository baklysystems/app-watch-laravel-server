<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Appswatch Weekly Report — {{ $project->name }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1f2937; font-size: 14px; line-height: 1.6; max-width: 700px; margin: 0 auto; padding: 20px; }
        h1 { font-size: 22px; margin-bottom: 4px; color: #111827; }
        h2 { font-size: 16px; margin-top: 28px; margin-bottom: 12px; color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px; }
        .subtitle { color: #6b7280; font-size: 13px; margin-top: 0; }
        .score-badge { display: inline-block; padding: 8px 20px; border-radius: 12px; font-size: 28px; font-weight: 700; }
        .score-green { background: #d1fae5; color: #065f46; }
        .score-yellow { background: #fef3c7; color: #92400e; }
        .score-red { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { text-align: left; padding: 8px 6px; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        td { padding: 8px 6px; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
        .metric-grid { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
        .metric-card { flex: 1; min-width: 140px; background: #f9fafb; border-radius: 10px; padding: 14px; text-align: center; }
        .metric-value { font-size: 22px; font-weight: 700; color: #111827; }
        .metric-label { font-size: 11px; color: #6b7280; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .footer { margin-top: 36px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
        .tag { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .tag-red { background: #fee2e2; color: #991b1b; }
        .tag-green { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <h1>{{ $project->name }} — Weekly Report</h1>
    <p class="subtitle">{{ $startDate->format('M j, Y') }} → {{ $endDate->format('M j, Y') }} · {{ $project->environment }}</p>

    <!-- Health Score -->
    <h2>📊 Health Score</h2>
    <div style="text-align: center; margin: 20px 0;">
        <span class="score-badge
            @if($healthScore['total'] >= 75) score-green
            @elseif($healthScore['total'] >= 50) score-yellow
            @else score-red @endif">
            {{ $healthScore['grade'] }} — {{ $healthScore['total'] }}/100
        </span>
        <p style="margin-top: 8px; font-size: 12px; color: #6b7280;">
            Error Rate: {{ $healthScore['scores']['error_rate'] ?? 0 }} · Uptime: {{ $healthScore['scores']['uptime'] ?? 0 }} · Response: {{ $healthScore['scores']['response_time'] ?? 0 }} · Queue: {{ $healthScore['scores']['queue_health'] ?? 0 }}
        </p>
    </div>

    <!-- Metrics Grid -->
    <h2>📈 Key Metrics</h2>
    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-value">{{ number_format((int)($exceptionSummary->total ?? 0)) }}</div>
            <div class="metric-label">Exceptions</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ number_format((int)($exceptionSummary->new_count ?? 0)) }}</div>
            <div class="metric-label">New/Unresolved</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ round((float)$uptimePct, 2) }}%</div>
            <div class="metric-label">Uptime</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ round((float)$avgResponseTime) }}ms</div>
            <div class="metric-label">Avg Response</div>
        </div>
    </div>

    <!-- Exception Summary -->
    <h2>🔴 Top Exceptions</h2>
    @if(isset($topExceptions) && $topExceptions->isNotEmpty())
    <table>
        <thead>
            <tr><th>Exception</th><th>Count</th><th>Message</th></tr>
        </thead>
        <tbody>
            @foreach($topExceptions as $ex)
            <tr>
                <td style="font-weight: 500;">{{ class_basename($ex->class) }}</td>
                <td><span class="tag tag-red">{{ $ex->occurrence_count }}</span></td>
                <td style="font-size: 12px; color: #4b5563;">{{ \Illuminate\Support\Str::limit($ex->message, 70) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #6b7280; font-size: 13px;">No exceptions this week.</p>
    @endif

    <!-- Queue Health -->
    <h2>⚡ Queue Health</h2>
    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-value">{{ number_format((int)($queueStats->total ?? 0)) }}</div>
            <div class="metric-label">Total Jobs</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ number_format((int)($queueStats->failed ?? 0)) }}</div>
            <div class="metric-label">Failed</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ round((float)($queueStats->avg_duration ?? 0)) }}ms</div>
            <div class="metric-label">Avg Duration</div>
        </div>
    </div>

    <!-- Revenue (Stripe) -->
    @if($revenueMetrics > 0)
    <h2>💰 Revenue (Stripe)</h2>
    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-value">${{ number_format((float)$revenueMetrics, 2) }}</div>
            <div class="metric-label">Successful Charges</div>
        </div>
    </div>
    @endif

    <!-- Traffic (GA4) -->
    @if($pageViews > 0 || $activeUsers > 0)
    <h2>🌐 Traffic (Google Analytics)</h2>
    <div class="metric-grid">
        <div class="metric-card">
            <div class="metric-value">{{ number_format((int)$pageViews) }}</div>
            <div class="metric-label">Page Views</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{{ number_format((int)$activeUsers) }}</div>
            <div class="metric-label">Active Users</div>
        </div>
    </div>
    @endif

    <div class="footer">
        Generated by Appswatch · {{ now()->format('F j, Y') }} · <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
    </div>
</body>
</html>