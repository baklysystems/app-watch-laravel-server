<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Models\Metric;
use Illuminate\Http\Request;

class MetricController extends IngestionController
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->validateBatch($request);
        $project = $this->getProject($request);
        $ingested = 0;

        foreach ($data['batch'] as $item) {
            try {
                Metric::create([
                    'project_id' => $project->id,
                    'name' => $item['name'] ?? 'unknown',
                    'value' => $item['value'] ?? 0,
                    'unit' => $item['unit'] ?? null,
                    'tags' => $item['tags'] ?? null,
                    'type' => $item['type'] ?? 'gauge',
                    'recorded_at' => $item['recorded_at'] ?? now(),
                ]);
                $ingested++;
            } catch (\Throwable $e) {
                // skip
            }
        }

        return $this->successResponse($ingested);
    }
}