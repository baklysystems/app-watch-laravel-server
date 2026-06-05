<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ExceptionsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Exceptions
    Route::get('/exceptions', [ExceptionsController::class, 'index'])->name('exceptions.index');
    Route::get('/exceptions/{id}', [ExceptionsController::class, 'show'])->name('exceptions.show');
    Route::patch('/exceptions/{id}/status', [ExceptionsController::class, 'updateStatus'])->name('exceptions.update-status');

    // Logs
    Route::get('/logs', [\App\Http\Controllers\Web\LogsController::class, 'index'])->name('logs.index');
    Route::get('/logs/{id}', [\App\Http\Controllers\Web\LogsController::class, 'show'])->name('logs.show');

    // Queues
    Route::get('/queues', [\App\Http\Controllers\Web\QueuesController::class, 'index'])->name('queues.index');
    Route::get('/queues/{id}', [\App\Http\Controllers\Web\QueuesController::class, 'show'])->name('queues.show');

    // Performance — Queries
    Route::get('/performance/queries', [\App\Http\Controllers\Web\PerformanceController::class, 'queries'])->name('performance.queries');
    Route::get('/performance/requests', [\App\Http\Controllers\Web\PerformanceController::class, 'requests'])->name('performance.requests');

    // Scheduled Tasks
    Route::get('/schedules', [\App\Http\Controllers\Web\SchedulesController::class, 'index'])->name('schedules.index');

    // Custom Metrics
    Route::get('/metrics', [\App\Http\Controllers\Web\MetricsController::class, 'index'])->name('metrics.index');

    // Alerts
    Route::get('/alerts', [\App\Http\Controllers\Web\AlertsController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/create', [\App\Http\Controllers\Web\AlertsController::class, 'create'])->name('alerts.create');
    Route::post('/alerts', [\App\Http\Controllers\Web\AlertsController::class, 'store'])->name('alerts.store');
    Route::get('/alerts/{id}/edit', [\App\Http\Controllers\Web\AlertsController::class, 'edit'])->name('alerts.edit');
    Route::patch('/alerts/{id}', [\App\Http\Controllers\Web\AlertsController::class, 'update'])->name('alerts.update');
    Route::patch('/alerts/{id}/toggle', [\App\Http\Controllers\Web\AlertsController::class, 'toggle'])->name('alerts.toggle');
    Route::delete('/alerts/{id}', [\App\Http\Controllers\Web\AlertsController::class, 'destroy'])->name('alerts.destroy');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Web\SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/project/{id}', [\App\Http\Controllers\Web\SettingsController::class, 'updateProject'])->name('settings.update-project');
    Route::post('/settings/project/{id}/api-key', [\App\Http\Controllers\Web\SettingsController::class, 'generateApiKey'])->name('settings.generate-api-key');
    Route::delete('/settings/api-key/{id}', [\App\Http\Controllers\Web\SettingsController::class, 'deleteApiKey'])->name('settings.delete-api-key');
    Route::delete('/settings/project/{id}', [\App\Http\Controllers\Web\SettingsController::class, 'deleteProject'])->name('settings.delete-project');
    Route::patch('/settings/project/{id}/integrations', [\App\Http\Controllers\Web\SettingsController::class, 'updateIntegrations'])->name('settings.update-integrations');
    Route::post('/settings/project/{id}/test-service', [\App\Http\Controllers\Web\SettingsController::class, 'testService'])->name('settings.test-service');
    Route::post('/settings/project/{id}/test-sync', [\App\Http\Controllers\Web\SettingsController::class, 'testSync'])->name('settings.test-sync');

    // Projects
    Route::post('/projects', [\App\Http\Controllers\Web\SettingsController::class, 'createProject'])->name('projects.create');

    // Incident Timeline
    Route::get('/incidents/timeline', [\App\Http\Controllers\Web\IncidentController::class, 'timeline'])->name('incidents.timeline');

    // Audit Log
    Route::get('/audit-log', [\App\Http\Controllers\Web\AuditLogController::class, 'index'])->name('audit.index');

    // Saved Filters
    Route::get('/saved-filters', [\App\Http\Controllers\Web\SavedFilterController::class, 'index'])->name('saved-filters.index');
    Route::post('/saved-filters', [\App\Http\Controllers\Web\SavedFilterController::class, 'store'])->name('saved-filters.store');
    Route::put('/saved-filters/{filter}', [\App\Http\Controllers\Web\SavedFilterController::class, 'update'])->name('saved-filters.update');
    Route::delete('/saved-filters/{filter}', [\App\Http\Controllers\Web\SavedFilterController::class, 'destroy'])->name('saved-filters.destroy');

    // Integrations — dedicated detail pages
    Route::get('/integrations/google-analytics', [\App\Http\Controllers\Web\IntegrationsController::class, 'googleAnalytics'])->name('integrations.google-analytics');
    Route::get('/integrations/google-search-console', [\App\Http\Controllers\Web\IntegrationsController::class, 'googleSearchConsole'])->name('integrations.google-search-console');
    Route::get('/integrations/cloudflare', [\App\Http\Controllers\Web\IntegrationsController::class, 'cloudflare'])->name('integrations.cloudflare');
    Route::get('/integrations/microsoft-clarity', [\App\Http\Controllers\Web\IntegrationsController::class, 'microsoftClarity'])->name('integrations.microsoft-clarity');
    Route::get('/integrations/stripe', [\App\Http\Controllers\Web\IntegrationsController::class, 'stripe'])->name('integrations.stripe');
    Route::get('/integrations/github', [\App\Http\Controllers\Web\IntegrationsController::class, 'github'])->name('integrations.github');
    Route::get('/integrations/email', [\App\Http\Controllers\Web\IntegrationsController::class, 'email'])->name('integrations.email');
});

require __DIR__.'/auth.php';