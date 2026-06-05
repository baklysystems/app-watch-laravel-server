<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class RunBackupsCommand extends Command
{
    protected $signature = 'appswatch:integrations:run-backups';
    protected $description = 'Execute database backups for all configured projects.';

    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Running database backups...');

        $results = $backupService->backupAll();

        foreach ($results as $result) {
            $icon = $result['success'] ? '✅' : '❌';
            $size = $result['success'] ? number_format($result['size_bytes'] / 1024 / 1024, 2) . ' MB' : 'failed';
            $this->line("  {$icon} {$result['database']}@{$result['host']} — {$result['file']} ({$size})");
        }

        $this->newLine();
        $this->info(count($results) . ' backup(s) completed.');

        return self::SUCCESS;
    }
}