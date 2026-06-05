<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Models\ScheduledTask;
use Illuminate\Http\Request;

class ScheduleController extends IngestionController
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->validateBatch($request);
        $project = $this->getProject($request);
        $ingested = 0;

        foreach ($data['batch'] as $item) {
            try {
                ScheduledTask::create([
                    'project_id' => $project->id,
                    'command' => $item['command'] ?? 'unknown',
                    'description' => $item['description'] ?? null,
                    'expression' => $item['expression'] ?? null,
                    'status' => $item['status'] ?? 'started',
                    'output' => $item['output'] ?? null,
                    'duration_ms' => $item['duration_ms'] ?? null,
                    'started_at' => $item['started_at'] ?? null,
                    'finished_at' => $item['finished_at'] ?? null,
                ]);
                $ingested++;
            } catch (\Throwable $e) {
                // skip
            }
        }

        return $this->successResponse($ingested);
    }
}