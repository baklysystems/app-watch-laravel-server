<?php

namespace App\Console\Commands;

use App\Services\AutoResolutionService;
use Illuminate\Console\Command;

class EvaluateAutoResolutionRulesCommand extends Command
{
    protected $signature = 'appswatch:evaluate-auto-resolution-rules';
    protected $description = 'Evaluate auto-resolution rules and apply them to matching exceptions';

    public function handle(AutoResolutionService $service): int
    {
        $this->info('Evaluating auto-resolution rules...');
        $service->evaluate();
        $this->info('Auto-resolution rules evaluated.');
        return self::SUCCESS;
    }
}