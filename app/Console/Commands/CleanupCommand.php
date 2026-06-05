<?php

namespace App\Console\Commands;

use App\Services\RetentionService;
use Illuminate\Console\Command;

class CleanupCommand extends Command
{
    protected $signature = 'appswatch:cleanup
                            {--project= : Specific project slug to clean}
                            {--dry-run : Preview records that would be deleted without actually deleting}';

    protected $description = 'Delete old records that exceed each project\'s retention period.';

    public function handle(RetentionService $retention): int
    {
        if ($this->option('project')) {
            return $this->cleanupSingleProject($retention);
        }

        return $this->cleanupAllProjects($retention);
    }

    protected function cleanupAllProjects(RetentionService $retention): int
    {
        $this->info('Starting retention cleanup across all active projects...');

        $results = $retention->cleanup();

        $total = 0;
        foreach ($results as $slug => $stats) {
            $subtotal = array_sum($stats);
            $total += $subtotal;

            $this->line("  <fg=cyan>{$slug}:</> {$subtotal} records deleted");
            foreach ($stats as $type => $count) {
                if ($count > 0) {
                    $this->line("    {$type}: {$count}");
                }
            }
        }

        $this->newLine();
        $this->info("Total records cleaned: {$total}");

        return self::SUCCESS;
    }

    protected function cleanupSingleProject(RetentionService $retention): int
    {
        $slug = $this->option('project');
        $project = \App\Models\Project::where('slug', $slug)->first();

        if (!$project) {
            $this->error("Project '{$slug}' not found.");
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run for project '{$slug}' (retention: {$project->retention_days} days):");

            $preview = $retention->preview($project);
            $total = array_sum($preview);

            foreach ($preview as $type => $count) {
                if ($count > 0) {
                    $this->line("  {$type}: {$count} would be deleted");
                }
            }

            $this->newLine();
            $this->info("Total that would be deleted: {$total}");

            return self::SUCCESS;
        }

        $this->info("Cleaning project '{$slug}' (retention: {$project->retention_days} days)...");

        $stats = $retention->cleanupProject($project);
        $total = array_sum($stats);

        foreach ($stats as $type => $count) {
            if ($count > 0) {
                $this->line("  {$type}: {$count}");
            }
        }

        $this->newLine();
        $this->info("Total records cleaned: {$total}");

        return self::SUCCESS;
    }
}