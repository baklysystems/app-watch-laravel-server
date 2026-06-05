<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public static function log(
        string $projectId,
        ?string $userId,
        string $action,
        string $entityType,
        string $entityId,
        $oldValues = null,
        $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'project_id'  => $projectId,
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => app()->runningInConsole() ? null : request()->ip(),
            'user_agent'  => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }
}