<?php

namespace App\Console\Commands;

use App\Services\UptimeService;
use Illuminate\Console\Command;

class CheckUptimeCommand extends Command
{
    protected $signature = 'appswatch:integrations:check-uptime';
    protected $description = 'Check HTTP health for all configured endpoints across projects.';

    public function handle(UptimeService $uptime): int
    {
        $this->info('Running uptime checks...');

        $results = $uptime->checkAll();

        $total = count($results);
        $up = count(array_filter($results, fn($r) => $r['success']));
        $down = $total - $up;

        foreach ($results as $result) {
            $icon = $result['success'] ? '✅' : '❌';
            $time = number_format($result['duration_ms'], 0) . 'ms';
            $this->line("  {$icon} {$result['url']} — HTTP {$result['status_code']} ({$time})");
        }

        $this->newLine();
        $this->info("Checked {$total} endpoints: {$up} up, {$down} down");

        return self::SUCCESS;
    }
}