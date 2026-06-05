<?php

namespace App\Console\Commands\Integrations;

use App\Models\Project;
use App\Services\Integrations\GoogleSearchConsoleService;
use Illuminate\Console\Command;

class PollGoogleSearchConsoleCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-gsc';
    protected $description = 'Poll Google Search Console metrics for enabled projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->google_search_console->enabled', true)
            ->get();

        if ($projects->isEmpty()) {
            $this->line('No projects with Google Search Console enabled.');
            return self::SUCCESS;
        }

        $service = app(GoogleSearchConsoleService::class);

        foreach ($projects as $project) {
            $service->fetchAndStoreMetrics($project);
            $this->line("GSC: Polled metrics for {$project->name}");
        }

        $this->info('Google Search Console metrics refreshed.');
        return self::SUCCESS;
    }
}