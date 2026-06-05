<?php

namespace App\Console\Commands;

use App\Jobs\SendReportEmail;
use App\Models\Project;
use Illuminate\Console\Command;

class SendWeeklyReportCommand extends Command
{
    protected $signature = 'appswatch:send-weekly-report';
    protected $description = 'Generate and email weekly reports for all active projects';

    public function handle(): int
    {
        $projects = Project::where('is_active', true)->get();

        if ($projects->isEmpty()) {
            $this->line('No active projects found.');
            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $reportConfig = $project->integrations_config['reporting'] ?? [];
            $enabled = $reportConfig['weekly_report_enabled'] ?? false;
            $recipients = $reportConfig['report_recipients'] ?? [];

            if (!$enabled || empty($recipients)) {
                $this->line("Report: Skipping {$project->name} (not enabled or no recipients).");
                continue;
            }

            SendReportEmail::dispatch($project, $recipients);
            $this->line("Report: Queued for {$project->name} → " . implode(', ', $recipients));
        }

        $this->info('Weekly reports queued.');
        return self::SUCCESS;
    }
}