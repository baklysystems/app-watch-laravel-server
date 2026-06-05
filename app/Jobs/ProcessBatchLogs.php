<?php

namespace App\Jobs;

use App\Models\LogEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ProcessBatchLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $projectId,
        protected array $batch,
    ) {}

    public function handle(): void
    {
        $entries = [];

        foreach ($this->batch as $item) {
            $entries[] = [
                'id' => (string) Str::uuid(),
                'project_id' => $this->projectId,
                'batch_id' => $item['batch_id'] ?? null,
                'level' => $item['level'] ?? 'info',
                'message' => $item['message'] ?? null,
                'context' => $item['context'] ?? null,
                'channel' => $item['channel'] ?? null,
                'file' => $item['file'] ?? null,
                'line' => $item['line'] ?? null,
                'trace_id' => $item['trace_id'] ?? null,
                'occurred_at' => $item['occurred_at'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($entries)) {
            LogEntry::insert($entries);
        }
    }
}