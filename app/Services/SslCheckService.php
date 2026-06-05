<?php

namespace App\Services;

use App\Models\IntegrationMetric;
use App\Models\Project;
use Carbon\Carbon;

class SslCheckService
{
    /**
     * Check SSL certificates for all configured domains across projects.
     */
    public function checkAll(): array
    {
        $results = [];
        $projects = Project::where('is_active', true)->get();

        foreach ($projects as $project) {
            $config = $project->integrations_config['ssl_monitor'] ?? null;
            if (!$config || !($config['enabled'] ?? false)) {
                continue;
            }

            foreach ($config['domains'] ?? [] as $domain) {
                $results[] = $this->checkDomain($project, $domain);
            }
        }

        return $results;
    }

    /**
     * Check SSL certificate for a single domain.
     */
    public function checkDomain(Project $project, string $domain): array
    {
        $certInfo = $this->fetchCertificate($domain);

        if ($certInfo) {
            $expiryDays = $certInfo['expiry_days'];
            $isValid = $certInfo['is_valid'];
            $issuer = $certInfo['issuer'];
        } else {
            $expiryDays = 0;
            $isValid = false;
            $issuer = 'unknown';
        }

        // Store metrics
        IntegrationMetric::create([
            'project_id' => $project->id,
            'integration' => 'ssl_check',
            'metric_name' => 'expiry_days',
            'metric_value' => $expiryDays,
            'unit' => 'days',
            'dimensions' => ['domain' => $domain, 'issuer' => $issuer],
            'recorded_at' => now(),
        ]);

        IntegrationMetric::create([
            'project_id' => $project->id,
            'integration' => 'ssl_check',
            'metric_name' => 'is_valid',
            'metric_value' => $isValid ? 1 : 0,
            'unit' => 'bool',
            'dimensions' => ['domain' => $domain],
            'recorded_at' => now(),
        ]);

        return [
            'domain' => $domain,
            'project' => $project->slug,
            'expiry_days' => $expiryDays,
            'is_valid' => $isValid,
            'issuer' => $issuer,
        ];
    }

    /**
     * Fetch the SSL certificate from a domain using stream socket.
     */
    protected function fetchCertificate(string $domain): ?array
    {
        $domain = parse_url('https://' . $domain, PHP_URL_HOST) ?: $domain;

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $socket = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return null;
        }

        $params = stream_context_get_params($socket);
        fclose($socket);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        if (!$cert) {
            return null;
        }

        $certData = openssl_x509_parse($cert);
        if (!$certData) {
            return null;
        }

        $validTo = $certData['validTo_time_t'] ?? 0;
        $now = time();
        $expiryDays = $validTo > 0 ? (int) round(($validTo - $now) / 86400) : 0;

        $issuer = '';
        if (isset($certData['issuer']['O'])) {
            $issuer = $certData['issuer']['O'];
        } elseif (isset($certData['issuer']['CN'])) {
            $issuer = $certData['issuer']['CN'];
        }

        return [
            'expiry_days' => $expiryDays,
            'issuer' => $issuer,
            'is_valid' => $expiryDays > 0,
        ];
    }
}