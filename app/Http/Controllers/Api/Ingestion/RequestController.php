<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Models\HttpRequest as HttpRequestModel;
use Illuminate\Http\Request;

class RequestController extends IngestionController
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->validateBatch($request);
        $project = $this->getProject($request);
        $ingested = 0;

        foreach ($data['batch'] as $item) {
            try {
                HttpRequestModel::create([
                    'project_id' => $project->id,
                    'trace_id' => $item['trace_id'] ?? (string) \Illuminate\Support\Str::uuid(),
                    'method' => $item['method'] ?? 'GET',
                    'url' => $item['url'] ?? '',
                    'route_name' => $item['route_name'] ?? null,
                    'controller_action' => $item['controller_action'] ?? null,
                    'status_code' => $item['status_code'] ?? 200,
                    'duration_ms' => $item['duration_ms'] ?? 0,
                    'memory_usage_mb' => $item['memory_usage_mb'] ?? 0,
                    'request_headers' => $item['request_headers'] ?? null,
                    'request_body' => $item['request_body'] ?? null,
                    'response_headers' => $item['response_headers'] ?? null,
                    'response_body' => $item['response_body'] ?? null,
                    'ip_address' => $item['ip_address'] ?? null,
                    'user_agent' => $item['user_agent'] ?? null,
                    'user_id' => $item['user_id'] ?? null,
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