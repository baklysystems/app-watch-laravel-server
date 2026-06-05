<?php

namespace App\Console\Commands;

use App\Services\AnomalyDetectionService;
use Illuminate\Console\Command;

class DetectAnomaliesCommand extends Command
{
    protected $signature = 'appswatch:detect-anomalies';
    protected $description = 'Run statistical anomaly detection on exception rates, response times, and queue failures';

    public function handle(AnomalyDetectionService $service): int
    {
        $this->info('Running anomaly detection...');

        $summary = $service->runAllChecks();

        $total = array_sum($summary);
        $this->info("Anomaly detection complete. {$total} anomalies found across " . count($summary) . " projects.");

        foreach ($summary as $project => $count) {
            if ($count > 0) {
                $this->warn("  {$project}: {$count} anomalies");
            }
        }

        return self::SUCCESS;
    }
}