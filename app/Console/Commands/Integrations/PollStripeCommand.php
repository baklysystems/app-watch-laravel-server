<?php

namespace App\Console\Commands\Integrations;

use App\Models\Project;
use App\Services\Integrations\StripeService;
use Illuminate\Console\Command;

class PollStripeCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-stripe';
    protected $description = 'Poll Stripe metrics for all enabled projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)
            ->whereJsonContains('integrations_config->stripe->enabled', true)
            ->get();

        $service = app(StripeService::class);
        foreach ($projects as $project) {
            $service->fetchAndStoreMetrics($project);
            $this->line("Stripe: {$project->name} — metrics stored.");
        }

        $this->info('Stripe polling complete.');
        return self::SUCCESS;
    }
}