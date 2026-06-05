<?php
namespace App\Services;

use App\Models\AppException;
use App\Models\AutoResolutionRule;

class AutoResolutionService
{
    public function evaluate(): void
    {
        $rules = AutoResolutionRule::with('project')->where('is_active', true)->get()->groupBy('project_id');

        foreach ($rules as $projectId => $projectRules) {
            $exceptions = AppException::where('project_id', $projectId)
                ->whereIn('status', ['unresolved', 'muted'])
                ->get();

            foreach ($projectRules as $rule) {
                $matches = $exceptions->filter(fn($e) => $this->matchesRule($e, $rule));
                foreach ($matches as $exception) {
                    $newStatus = match ($rule->rule_type) {
                        'auto_resolve' => 'resolved',
                        'auto_mute'   => 'muted',
                        'auto_ignore' => 'ignored',
                        default       => null,
                    };
                    if (!$newStatus) continue;
                    $exception->update(['status' => $newStatus]);
                    AuditService::log($projectId, null, "auto_{$rule->rule_type}", 'Exception', $exception->id, ['status' => 'unresolved'], ['status' => $newStatus]);
                    $rule->increment('execution_count');
                }
                $rule->update(['last_evaluated_at' => now()]);
            }
        }
    }

    private function matchesRule($exception, $rule): bool
    {
        $c = $rule->conditions;
        if (!empty($c['class']) && !str_contains($exception->class, $c['class'])) return false;
        if (!empty($c['environment']) && !in_array($exception->environment, (array) $c['environment'])) return false;
        if (!empty($c['days_unresolved']) && $exception->last_seen_at?->diffInDays(now()) < $c['days_unresolved']) return false;
        if (!empty($c['max_occurrence_count']) && $exception->occurrence_count > $c['max_occurrence_count']) return false;
        if (!empty($c['message_pattern']) && !preg_match($c['message_pattern'], $exception->message ?? '')) return false;
        if (!empty($c['file_pattern']) && !preg_match($c['file_pattern'], $exception->file ?? '')) return false;
        return true;
    }
}