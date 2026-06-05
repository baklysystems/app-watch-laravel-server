<?php

namespace App\Console\Commands\Integrations;

use App\Models\Project;
use App\Services\Integrations\GoogleAnalyticsService;
use Illuminate\Console\Command;

class PollGoogleAnalyticsCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-ga4';
    protected $description = 'Poll Google Analytics 4 metrics for all enabled projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->google_analytics->enabled', true)
            ->get();

        $service = app(GoogleAnalyticsService::class);
        foreach ($projects as $project) {
            $service->fetchAndStoreMetrics($project);
            $this->line("GA4: {$project->name} — metrics stored.");
        }

        $this->info('GA4 polling complete.');
        return self::SUCCESS;
    }
}