<?php

namespace App\Support\Interview;

use App\Models\InterviewAuditLog;
use App\Models\User;

class InterviewAuditLogger
{
    public static function log(
        string $action,
        string $description,
        ?User $user = null,
        ?int $sessionId = null,
        ?array $meta = null
    ): void {
        InterviewAuditLog::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'action' => $action,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
