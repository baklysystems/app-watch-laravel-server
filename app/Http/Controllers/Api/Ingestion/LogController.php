<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Models\LogEntry;
use Illuminate\Http\Request;

class LogController extends IngestionController
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->validateBatch($request);
        $project = $this->getProject($request);

        $ingested = 0;
        $entries = [];

        foreach ($data['batch'] as $item) {
            $entries[] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'project_id' => $project->id,
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
            $ingested++;
        }

        if (!empty($entries)) {
            try {
                LogEntry::insert($entries);
            } catch (\Throwable $e) {
                // Fallback to individual inserts
                foreach ($entries as $entry) {
                    try {
                        LogEntry::create($entry);
                    } catch (\Throwable $e2) {
                        // Skip
                    }
                }
            }
        }

        return $this->successResponse($ingested);
    }
}