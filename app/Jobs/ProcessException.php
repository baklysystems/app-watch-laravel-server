<?php

namespace App\Jobs;

use App\Models\AppException;
use App\Services\ExceptionFingerprinter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessException implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $projectId,
        protected array $item,
    ) {}

    public function handle(ExceptionFingerprinter $fingerprinter): void
    {
        $class = $this->item['class'] ?? 'Unknown';
        $file = $this->item['file'] ?? '';
        $line = (int) ($this->item['line'] ?? 0);

        // Generate fingerprint if not provided
        $fingerprint = $this->item['fingerprint'] ?? null;
        if (!$fingerprint && $file && $line) {
            $fingerprint = $fingerprinter->generate($class, $file, $line);
        } elseif (!$fingerprint) {
            $fingerprint = md5($class . '|' . ($this->item['message'] ?? ''));
        }

        // Upsert: update existing or create new
        $existing = AppException::where('project_id', $this->projectId)
            ->where('fingerprint', $fingerprint)
            ->first();

        if ($existing) {
            $existing->update([
                'message' => $this->item['message'] ?? $existing->message,
                'occurrence_count' => $existing->occurrence_count + 1,
                'last_seen_at' => $this->item['occurred_at'] ?? now(),
            ]);

            // If a resolved exception re-occurs, it auto-reopens
            if (in_array($existing->status, ['resolved', 'ignored'])) {
                $existing->update(['status' => 'unresolved']);
            }
        } else {
            AppException::create([
                'project_id' => $this->projectId,
                'fingerprint' => $fingerprint,
                'class' => $class,
                'message' => $this->item['message'] ?? null,
                'file' => $file ?: null,
                'line' => $line ?: null,
                'code_snippet' => $this->item['code_snippet'] ?? null,
                'stack_trace' => $this->item['stack_trace'] ?? null,
                'request_data' => $this->item['request_data'] ?? null,
                'user_data' => $this->item['user_data'] ?? null,
                'breadcrumbs' => $this->item['breadcrumbs'] ?? null,
                'environment' => $this->item['environment'] ?? 'production',
                'release' => $this->item['release'] ?? null,
                'severity' => $this->item['severity'] ?? 'error',
                'status' => 'unresolved',
                'occurrence_count' => 1,
                'first_seen_at' => $this->item['occurred_at'] ?? now(),
                'last_seen_at' => $this->item['occurred_at'] ?? now(),
            ]);
        }
    }
}