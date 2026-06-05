<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Models\AppException;
use Illuminate\Http\Request;

class ExceptionController extends IngestionController
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->validateBatch($request);
        $project = $this->getProject($request);

        $ingested = 0;
        $errors = [];

        foreach ($data['batch'] as $index => $item) {
            try {
                $fingerprint = $item['fingerprint'] ?? md5(($item['class'] ?? '') . '|' . ($item['file'] ?? '') . '|' . ($item['line'] ?? 0));

                // Check for existing exception by fingerprint
                $existing = AppException::where('project_id', $project->id)
                    ->where('fingerprint', $fingerprint)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'message' => $item['message'] ?? $existing->message,
                        'occurrence_count' => $existing->occurrence_count + 1,
                        'last_seen_at' => $item['occurred_at'] ?? now(),
                    ]);
                } else {
                    AppException::create([
                        'project_id' => $project->id,
                        'fingerprint' => $fingerprint,
                        'class' => $item['class'] ?? 'Unknown',
                        'message' => $item['message'] ?? null,
                        'file' => $item['file'] ?? null,
                        'line' => $item['line'] ?? null,
                        'code_snippet' => $item['code_snippet'] ?? null,
                        'stack_trace' => $item['stack_trace'] ?? null,
                        'request_data' => $item['request_data'] ?? null,
                        'user_data' => $item['user_data'] ?? null,
                        'breadcrumbs' => $item['breadcrumbs'] ?? null,
                        'environment' => $item['environment'] ?? $project->environment,
                        'release' => $item['release'] ?? null,
                        'severity' => $item['severity'] ?? 'error',
                        'status' => 'unresolved',
                        'occurrence_count' => 1,
                        'first_seen_at' => $item['occurred_at'] ?? now(),
                        'last_seen_at' => $item['occurred_at'] ?? now(),
                    ]);
                }

                $ingested++;
            } catch (\Throwable $e) {
                $errors[] = "Item {$index}: {$e->getMessage()}";
            }
        }

        return $this->successResponse($ingested, count($errors), $errors);
    }
}