<?php

namespace App\Services;

use App\Models\IntegrationMetric;
use App\Models\Project;
use Illuminate\Support\Facades\Process;

class DatabaseBackupService
{
    /**
     * Execute database backups for all configured projects using mysqldump.
     */
    public function backupAll(): array
    {
        $results = [];
        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $config = $project->integrations_config['db_backups'] ?? null;
            if (!$config || !($config['enabled'] ?? false)) {
                continue;
            }

            foreach ($config['servers'] ?? [] as $server) {
                $results[] = $this->backupServer($project, $server, $config['storage_path'] ?? storage_path('backups'));
            }
        }

        return $results;
    }

    /**
     * Backup a single database server.
     */
    public function backupServer(Project $project, array $server, string $storagePath): array
    {
        $host = $server['host'] ?? 'localhost';
        $database = $server['database'] ?? $project->slug;
        $dbType = $server['db_type'] ?? 'mysql';
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "{$project->slug}_{$database}_{$timestamp}.sql.gz";

        $backupDir = rtrim($storagePath, '/') . '/' . $project->slug;
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filePath = $backupDir . '/' . $filename;
        $success = false;
        $fileSize = 0;

        try {
            if ($dbType === 'mysql') {
                $success = $this->mysqlDump($server, $filePath);
            } elseif ($dbType === 'pgsql') {
                $success = $this->pgDump($server, $filePath);
            }

            if ($success && file_exists($filePath)) {
                $fileSize = filesize($filePath);
            }
        } catch (\Throwable $e) {
            $success = false;
        }

        // Store metric
        IntegrationMetric::create([
            'project_id' => $project->id,
            'integration' => 'db_backup',
            'metric_name' => 'backup_success',
            'metric_value' => $success ? 1 : 0,
            'unit' => 'bool',
            'dimensions' => ['database' => $database, 'host' => $host, 'file' => $filename],
            'recorded_at' => now(),
        ]);

        if ($success) {
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'db_backup',
                'metric_name' => 'backup_size_bytes',
                'metric_value' => (float) $fileSize,
                'unit' => 'bytes',
                'dimensions' => ['database' => $database, 'host' => $host],
                'recorded_at' => now(),
            ]);
        }

        return [
            'project' => $project->slug,
            'database' => $database,
            'host' => $host,
            'file' => $filename,
            'size_bytes' => $fileSize,
            'success' => $success,
        ];
    }

    /**
     * Run mysqldump and gzip the output.
     */
    protected function mysqlDump(array $server, string $filePath): bool
    {
        $host = $server['host'] ?? '127.0.0.1';
        $port = $server['port'] ?? '3306';
        $database = $server['database'];
        $user = $server['db_user'] ?? config('database.connections.mysql.username');
        $password = $server['db_password'] ?? config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s 2>/dev/null | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        exec($command, $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * Run pg_dump and gzip the output.
     */
    protected function pgDump(array $server, string $filePath): bool
    {
        $host = $server['host'] ?? '127.0.0.1';
        $port = $server['port'] ?? '5432';
        $database = $server['database'];
        $user = $server['db_user'] ?? 'postgres';

        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s 2>/dev/null | gzip > %s',
            escapeshellarg($server['db_password'] ?? ''),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        exec($command, $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * Rotate old backups beyond retention period.
     */
    public function rotate(Project $project, array $server, string $storagePath): int
    {
        $retentionDays = $server['retention_days'] ?? 30;
        $backupDir = rtrim($storagePath, '/') . '/' . $project->slug;
        $cutoff = now()->subDays($retentionDays)->timestamp;
        $deleted = 0;

        if (!is_dir($backupDir)) {
            return 0;
        }

        $files = glob($backupDir . '/*.sql.gz');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}