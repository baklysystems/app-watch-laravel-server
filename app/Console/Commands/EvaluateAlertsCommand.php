<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class EvaluateAlertsCommand extends Command
{
    protected $signature = 'appswatch:evaluate-alerts';
    protected $description = 'Evaluate all alert rules and send notifications for triggered ones.';

    public function handle(AlertService $alertService): int
    {
        $this->info('Evaluating alert rules...');

        $triggered = $alertService->evaluateAll();

        if (empty($triggered)) {
            $this->info('No alerts triggered.');
            return self::SUCCESS;
        }

        foreach ($triggered as $result) {
            $this->line("  🔔 {$result['alert']} ({$result['type']})");
            foreach ($result['details'] as $key => $value) {
                $this->line("    {$key}: {$value}");
            }
        }

        $this->newLine();
        $this->info(count($triggered) . ' alert(s) triggered.');

        return self::SUCCESS;
    }
}