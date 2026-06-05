<?php

namespace App\Console\Commands\Integrations;

use App\Models\Project;
use App\Services\Integrations\MailgunService;
use App\Services\Integrations\PostmarkService;
use App\Services\Integrations\SesService;
use Illuminate\Console\Command;

class PollEmailProviderCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-email';
    protected $description = 'Poll email deliverability metrics for enabled projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->mail_provider->enabled', true)
            ->get();

        if ($projects->isEmpty()) {
            $this->line('No projects with email provider monitoring enabled.');
            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $provider = $project->integrations_config['mail_provider']['provider'] ?? 'mailgun';

            $service = match ($provider) {
                'postmark' => app(PostmarkService::class),
                'ses' => app(SesService::class),
                default => app(MailgunService::class),
            };

            $service->fetchAndStoreMetrics($project);
            $this->line("Email: Polled {$provider} metrics for {$project->name}");
        }

        $this->info('Email provider metrics refreshed.');
        return self::SUCCESS;
    }
}