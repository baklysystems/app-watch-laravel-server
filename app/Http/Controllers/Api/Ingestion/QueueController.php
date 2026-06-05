<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Models\QueueJob;
use Illuminate\Http\Request;

class QueueController extends IngestionController
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->validateBatch($request);
        $project = $this->getProject($request);
        $ingested = 0;

        foreach ($data['batch'] as $item) {
            try {
                QueueJob::create([
                    'project_id' => $project->id,
                    'connection' => $item['connection'] ?? null,
                    'queue' => $item['queue'] ?? null,
                    'job_name' => $item['job_name'] ?? 'unknown',
                    'payload' => $item['payload'] ?? null,
                    'attempt' => $item['attempt'] ?? 0,
                    'max_attempts' => $item['max_attempts'] ?? 1,
                    'status' => $item['status'] ?? 'pending',
                    'queued_at' => $item['queued_at'] ?? null,
                    'started_at' => $item['started_at'] ?? null,
                    'finished_at' => $item['finished_at'] ?? null,
                    'duration_ms' => $item['duration_ms'] ?? null,
                ]);
                $ingested++;
            } catch (\Throwable $e) {
                // skip
            }
        }

        return $this->successResponse($ingested);
    }
}