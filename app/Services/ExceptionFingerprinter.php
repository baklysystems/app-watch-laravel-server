<?php

namespace App\Services;

class ExceptionFingerprinter
{
    /**
     * Generate a fingerprint for an exception from its class, file, and line.
     * 
     * The fingerprint is used to group similar exceptions together.
     * Exceptions with the same class, file, and line pattern produce the same fingerprint.
     */
    public function generate(string $class, string $file, int $line): string
    {
        return md5($class . '|' . $file . '|' . $line);
    }

    /**
     * Generate a more granular fingerprint that includes the stack trace pattern.
     * This groups exceptions that share the same call path, not just the same origin.
     */
    public function generateWithTrace(string $class, string $file, int $line, array $stackTrace): string
    {
        $traceSignature = $this->buildTraceSignature($stackTrace);

        return md5($class . '|' . $file . '|' . $line . '|' . $traceSignature);
    }

    /**
     * Build a compact signature from the stack trace for fingerprinting.
     * Only includes file and function names, not line numbers (for better grouping).
     */
    protected function buildTraceSignature(array $trace): string
    {
        $frames = array_slice($trace, 0, 10);

        return implode('→', array_map(function ($frame) {
            $file = str_replace(base_path(), '', $frame['file'] ?? '');
            $func = $frame['function'] ?? '';
            $class = $frame['class'] ?? '';

            return ($class ? $class . '::' : '') . $func . '@' . $file;
        }, $frames));
    }

    /**
     * Check if two exceptions are likely similar based on fingerprint prefix.
     * Useful for suggesting "similar exceptions" in the UI.
     */
    public function areSimilar(string $fingerprintA, string $fingerprintB): bool
    {
        // First 8 chars of MD5 gives a reasonable fuzzy match
        return substr($fingerprintA, 0, 8) === substr($fingerprintB, 0, 8);
    }
}