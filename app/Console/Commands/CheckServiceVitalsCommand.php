<?php

namespace App\Console\Commands;

use App\Services\ServiceVitalsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckServiceVitalsCommand extends Command
{
    protected $signature = 'appswatch:check-service-vitals';
    protected $description = 'Check service vitals (mail, queue, notification, redis, reverb) for configured projects.';

    public function handle(ServiceVitalsService $service): int
    {
        $this->info('Checking service vitals...');

        try {
            $results = $service->checkAll();
            $count = count($results);

            foreach ($results as $slug => $checks) {
                $this->info("  {$slug}:");
                foreach ($checks as $name => $status) {
                    $icon = $status['ok'] ? '✅' : '❌';
                    $this->line("    {$icon} {$name}: {$status['details']['status']}");
                }
            }

            $this->info("Done. {$count} project(s) checked.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Service vitals check failed: ' . $e->getMessage());
            $this->error('Service vitals check failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}