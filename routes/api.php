<?php

use App\Http\Controllers\Api\Ingestion\ExceptionController;
use App\Http\Controllers\Api\Ingestion\LogController;
use App\Http\Controllers\Api\Ingestion\MetricController;
use App\Http\Controllers\Api\Ingestion\PingController;
use App\Http\Controllers\Api\Ingestion\QueueController;
use App\Http\Controllers\Api\Ingestion\QueryController;
use App\Http\Controllers\Api\Ingestion\RequestController;
use App\Http\Controllers\Api\Ingestion\ScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Appswatch Ingestion
|--------------------------------------------------------------------------
|
| These routes receive batched telemetry data from client apps running
| the baklysystems/appswatch package. All routes are protected by the
| VerifyApiKey middleware for API key authentication + rate limiting.
|
*/

use App\Http\Controllers\Api\TelegramWebhookController;

Route::middleware(['api', 'verify-api-key'])->group(function () {
    Route::post('/ingest/exceptions', ExceptionController::class);
    Route::post('/ingest/logs', LogController::class);
    Route::post('/ingest/queues', QueueController::class);
    Route::post('/ingest/queries', QueryController::class);
    Route::post('/ingest/requests', RequestController::class);
    Route::post('/ingest/schedules', ScheduleController::class);
    Route::post('/ingest/metrics', MetricController::class);
    Route::post('/ingest/ping', PingController::class);
    Route::get('/ingest/ping', PingController::class);
});

// Telegram Bot Webhook — requires webhook secret validation in controller
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Prometheus metrics endpoint
Route::get('/metrics/prometheus', [\App\Http\Controllers\Api\PrometheusMetricsController::class, '__invoke']);

// Stripe webhook
Route::post('/stripe/webhook', [\App\Http\Controllers\Api\StripeWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// GitHub webhook
Route::post('/github/webhook', [\App\Http\Controllers\Api\GitHubWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// GitLab webhook
Route::post('/gitlab/webhook', [\App\Http\Controllers\Api\GitLabWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Mailgun webhook
Route::post('/mailgun/webhook', [\App\Http\Controllers\Api\MailgunWebhookController::class, '__invoke'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
