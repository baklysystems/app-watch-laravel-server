<?php

namespace App\Console\Commands;

use App\Services\ServerMonitorService;
use Illuminate\Console\Command;

class CheckServersCommand extends Command
{
    protected $signature = 'appswatch:integrations:poll-servers';
    protected $description = 'Collect CPU, memory, and disk metrics from configured servers.';

    public function handle(ServerMonitorService $serverMonitor): int
    {
        $this->info('Collecting server metrics...');

        $results = $serverMonitor->collectAll();

        foreach ($results as $result) {
            $this->line("  🖥️ {$result['host']} ({$result['project']})");
            foreach ($result['metrics'] as $metric) {
                $this->line("    {$metric['name']}: {$metric['value']} {$metric['unit']}");
            }
        }

        $this->newLine();
        $this->info('Server metrics collected.');

        return self::SUCCESS;
    }
}