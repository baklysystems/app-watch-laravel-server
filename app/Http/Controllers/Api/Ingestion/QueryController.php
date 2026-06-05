<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Models\DatabaseQuery;
use Illuminate\Http\Request;

class QueryController extends IngestionController
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->validateBatch($request);
        $project = $this->getProject($request);
        $ingested = 0;

        foreach ($data['batch'] as $item) {
            try {
                DatabaseQuery::create([
                    'project_id' => $project->id,
                    'batch_id' => $item['batch_id'] ?? null,
                    'sql' => $item['sql'] ?? '',
                    'bindings' => $item['bindings'] ?? null,
                    'duration_ms' => $item['duration_ms'] ?? 0,
                    'connection_name' => $item['connection_name'] ?? null,
                    'file' => $item['file'] ?? null,
                    'line' => $item['line'] ?? null,
                    'is_slow' => $item['is_slow'] ?? false,
                    'trace_id' => $item['trace_id'] ?? null,
                    'occurred_at' => $item['occurred_at'] ?? now(),
                ]);
                $ingested++;
            } catch (\Throwable $e) {
                // skip
            }
        }

        return $this->successResponse($ingested);
    }
}