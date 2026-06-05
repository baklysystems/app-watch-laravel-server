<?php

namespace App\Console\Commands\Integrations;

use App\Models\Project;
use App\Services\Integrations\GitHubService;
use Illuminate\Console\Command;

class PollGitHubCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-github';
    protected $description = 'Poll GitHub deployments and workflow runs for enabled projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->github->enabled', true)
            ->get();

        if ($projects->isEmpty()) {
            $this->line('No projects with GitHub enabled.');
            return self::SUCCESS;
        }

        $service = app(GitHubService::class);

        foreach ($projects as $project) {
            $service->fetchAndStoreMetrics($project);
            $this->line("GitHub: Polled metrics for {$project->name}");
        }

        $this->info('GitHub metrics refreshed.');
        return self::SUCCESS;
    }
}