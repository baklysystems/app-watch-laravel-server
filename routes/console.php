<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Appswatch Scheduled Commands
|--------------------------------------------------------------------------
|
| All scheduled commands for the Appswatch monitoring platform.
| These run via the central server's cron scheduler.
|
*/

// Data Retention Cleanup — daily at 2 AM
Schedule::command('appswatch:cleanup')->dailyAt('02:00');

// Alert Evaluation — every minute
Schedule::command('appswatch:evaluate-alerts')->everyMinute();

// Uptime Checks — every minute
Schedule::command('appswatch:integrations:check-uptime')->everyMinute();

// Server Resource Monitoring — every 5 minutes
Schedule::command('appswatch:integrations:check-servers')->everyFiveMinutes();

// SSL Certificate Checks — daily at 3 AM
Schedule::command('appswatch:integrations:check-ssl')->dailyAt('03:00');

// Domain WHOIS Checks — daily at 4 AM
Schedule::command('appswatch:integrations:check-domains')->dailyAt('04:00');

// Database Backups — daily at 1 AM
Schedule::command('appswatch:integrations:run-backups')->dailyAt('01:00');

// Service Vitals — every 5 minutes
Schedule::command('appswatch:check-service-vitals')->everyFiveMinutes();

// Anomaly Detection — every 5 minutes
Schedule::command('appswatch:detect-anomalies')->everyFiveMinutes();

// Auto-Resolution Rules — every 5 minutes
Schedule::command('appswatch:evaluate-auto-resolution-rules')->everyFiveMinutes();

// Google Analytics 4 Polling — every 30 minutes
Schedule::command('appswatch:integrations:poll-ga4')->everyThirtyMinutes();

// Stripe Metrics Polling — every 30 minutes
Schedule::command('appswatch:integrations:poll-stripe')->everyThirtyMinutes();

// Google Search Console — every 6 hours
Schedule::command('appswatch:integrations:poll-gsc')->everySixHours();

// Cloudflare Analytics — every 15 minutes
Schedule::command('appswatch:integrations:poll-cloudflare')->everyFifteenMinutes();

// Microsoft Clarity — every 60 minutes
Schedule::command('appswatch:integrations:poll-clarity')->hourly();

// GitHub Deployments & Workflows — every 5 minutes
Schedule::command('appswatch:integrations:poll-github')->everyFiveMinutes();

// Email Provider Metrics — 3 times daily
Schedule::command('appswatch:integrations:poll-email')->cron('0 */8 * * *');

// Weekly Reports — Monday at 8 AM
Schedule::command('appswatch:send-weekly-report')->weeklyOn(1, '8:00');
