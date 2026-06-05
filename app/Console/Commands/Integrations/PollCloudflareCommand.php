<?php

namespace App\Console\Commands\Integrations;

use App\Models\Project;
use App\Services\Integrations\CloudflareService;
use Illuminate\Console\Command;

class PollCloudflareCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-cloudflare';
    protected $description = 'Poll Cloudflare analytics for enabled projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->cloudflare->enabled', true)
            ->get();

        if ($projects->isEmpty()) {
            $this->line('No projects with Cloudflare enabled.');
            return self::SUCCESS;
        }

        $service = app(CloudflareService::class);

        foreach ($projects as $project) {
            $service->fetchAndStoreMetrics($project);
            $this->line("Cloudflare: Polled metrics for {$project->name}");
        }

        $this->info('Cloudflare metrics refreshed.');
        return self::SUCCESS;
    }
}