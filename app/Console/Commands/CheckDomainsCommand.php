<?php

namespace App\Console\Commands;

use App\Services\DomainExpiryService;
use Illuminate\Console\Command;

class CheckDomainsCommand extends Command
{
    protected $signature = 'appswatch:integrations:check-domains';
    protected $description = 'Check WHOIS domain expiry for all configured domains.';

    public function handle(DomainExpiryService $domainExpiry): int
    {
        $this->info('Checking domain expiry...');

        $results = $domainExpiry->checkAll();

        foreach ($results as $result) {
            $days = $result['expiry_days'];
            $icon = $days <= 0 ? '❌' : ($days <= 30 ? '⚠️' : '✅');
            $date = $result['expiry_date'] ?? 'unknown';
            $registrar = $result['registrar'] ?? 'unknown';

            $this->line("  {$icon} {$result['domain']} — Expires: {$date} ({$days}d) — Registrar: {$registrar}");
        }

        $this->newLine();
        $this->info('Domain check complete.');

        return self::SUCCESS;
    }
}