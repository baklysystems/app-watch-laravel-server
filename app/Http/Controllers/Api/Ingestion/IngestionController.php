<?php

namespace App\Http\Controllers\Api\Ingestion;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

abstract class IngestionController extends Controller
{
    protected function getProject(Request $request): Project
    {
        return $request->attributes->get('project');
    }

    protected function successResponse(int $ingested, int $rejected = 0, array $errors = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'ingested' => $ingested,
            'rejected' => $rejected,
            'errors' => $errors,
        ]);
    }

    protected function validateBatch(Request $request): array
    {
        $data = $request->validate([
            'batch' => 'required|array|min:1',
            'batch.*' => 'required|array',
            'environment' => 'nullable|string',
        ]);

        return $data;
    }
}