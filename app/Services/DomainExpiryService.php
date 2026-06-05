<?php

namespace App\Services;

use App\Models\IntegrationMetric;
use App\Models\Project;

class DomainExpiryService
{
    /**
     * Check domain WHOIS expiry for all configured domains.
     */
    public function checkAll(): array
    {
        $results = [];
        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $config = $project->integrations_config['domain_monitor'] ?? null;
            if (!$config || !($config['enabled'] ?? false)) {
                continue;
            }

            foreach ($config['domains'] ?? [] as $domain) {
                $results[] = $this->checkDomain($project, $domain);
                sleep(2); // WHOIS rate limiting
            }
        }

        return $results;
    }

    /**
     * Check WHOIS expiry for a single domain using native PHP.
     */
    public function checkDomain(Project $project, string $domain): array
    {
        $expiryDate = null;
        $registrar = null;
        $expiryDays = null;

        try {
            $whois = $this->queryWhois($domain);
            $expiryDate = $this->parseExpiryDate($whois, $domain);
            $registrar = $this->parseRegistrar($whois);

            if ($expiryDate) {
                $expiryDays = (int) Carbon::now()->diffInDays($expiryDate, false);
            }
        } catch (\Throwable $e) {
            $expiryDays = null;
        }

        $metricValue = $expiryDays ?? -1;

        IntegrationMetric::create([
            'project_id' => $project->id,
            'integration' => 'domain_expiry',
            'metric_name' => 'expiry_days',
            'metric_value' => (float) $metricValue,
            'unit' => 'days',
            'dimensions' => ['domain' => $domain, 'registrar' => $registrar ?? 'unknown'],
            'recorded_at' => now(),
        ]);

        return [
            'domain' => $domain,
            'project' => $project->slug,
            'expiry_days' => $metricValue,
            'expiry_date' => $expiryDate?->toDateString(),
            'registrar' => $registrar,
        ];
    }

    /**
     * Query WHOIS server for domain info using native PHP sockets.
     */
    protected function queryWhois(string $domain): ?string
    {
        $domain = strtolower($domain);

        // Determine WHOIS server based on TLD
        $tld = str_contains($domain, '.') ? substr($domain, strrpos($domain, '.') + 1) : 'com';
        $server = match ($tld) {
            'org' => 'whois.pir.org',
            'net' => 'whois.verisign-grs.com',
            'io' => 'whois.nic.io',
            'co' => 'whois.nic.co',
            'uk' => 'whois.nic.uk',
            'de' => 'whois.denic.de',
            default => 'whois.verisign-grs.com',
        };

        $socket = @fsockopen($server, 43, $errno, $errstr, 10);
        if (!$socket) {
            return null;
        }

        fwrite($socket, $domain . "\r\n");
        stream_set_timeout($socket, 10);

        $response = '';
        while (!feof($socket)) {
            $response .= fread($socket, 8192);
        }
        fclose($socket);

        return $response;
    }

    /**
     * Parse expiry date from WHOIS response.
     */
    protected function parseExpiryDate(string $response, string $domain): ?Carbon
    {
        $patterns = [
            '/Registry Expiry Date:\s*(.+)/i',
            '/Expiry Date:\s*(.+)/i',
            '/expires:\s*(.+)/i',
            '/Renewal Date:\s*(.+)/i',
            '/paid-till:\s*(.+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                try {
                    return Carbon::parse(trim($matches[1]));
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Parse registrar from WHOIS response.
     */
    protected function parseRegistrar(string $response): ?string
    {
        $patterns = [
            '/Registrar:\s*(.+)/i',
            '/Sponsoring Registrar:\s*(.+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }
}