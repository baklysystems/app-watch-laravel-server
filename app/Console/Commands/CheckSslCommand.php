<?php

namespace App\Console\Commands;

use App\Services\SslCheckService;
use Illuminate\Console\Command;

class CheckSslCommand extends Command
{
    protected $signature = 'appswatch:integrations:check-ssl';
    protected $description = 'Check SSL certificate expiry for all configured domains.';

    public function handle(SslCheckService $ssl): int
    {
        $this->info('Checking SSL certificates...');

        $results = $ssl->checkAll();

        foreach ($results as $result) {
            $icon = $result['is_valid'] ? '✅' : '❌';
            $days = $result['expiry_days'];
            $warning = $days <= 30 ? ' ⚠️ EXPIRING SOON' : '';

            $this->line("  {$icon} {$result['domain']} — {$days} days — {$result['issuer']}{$warning}");
        }

        $this->newLine();
        $this->info('SSL check complete.');

        return self::SUCCESS;
    }
}