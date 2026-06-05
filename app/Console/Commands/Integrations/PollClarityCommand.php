<?php

namespace App\Console\Commands\Integrations;

use App\Models\Project;
use App\Services\Integrations\MicrosoftClarityService;
use Illuminate\Console\Command;

class PollClarityCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-clarity';
    protected $description = 'Poll Microsoft Clarity metrics for enabled projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->microsoft_clarity->enabled', true)
            ->get();

        if ($projects->isEmpty()) {
            $this->line('No projects with Microsoft Clarity enabled.');
            return self::SUCCESS;
        }

        $service = app(MicrosoftClarityService::class);

        foreach ($projects as $project) {
            $service->fetchAndStoreMetrics($project);
            $this->line("Clarity: Polled metrics for {$project->name}");
        }

        $this->info('Microsoft Clarity metrics refreshed.');
        return self::SUCCESS;
    }
}