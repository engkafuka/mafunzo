<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /** Roles whose changes are recorded in the audit trail. */
    private const AUDITABLE_ROLES = ['super_admin', 'admin', 'staff', 'trainer'];

    private const HIDDEN_ATTRIBUTES = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function shouldAudit(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && in_array($user->role, self::AUDITABLE_ROLES, true);
    }

    public static function logModelEvent(Model $model, string $event, ?array $oldValues = null): void
    {
        if (! self::shouldAudit()) {
            return;
        }

        if ($event === 'deleted') {
            self::write(
                event: 'deleted',
                description: self::modelDescription($model, 'deleted'),
                auditable: $model,
                oldValues: self::filterAttributes($model->getOriginal()),
                newValues: null,
            );

            return;
        }

        $newValues = self::filterAttributes($model->getAttributes());

        if ($event === 'updated') {
            $changes = self::filterAttributes($model->getChanges());
            if ($changes === []) {
                return;
            }
            $oldValues = $oldValues ?? self::extractOldValues($model, array_keys($changes));
            $newValues = $changes;
        }

        if ($event === 'created' && $newValues === []) {
            return;
        }

        self::write(
            event: $event,
            description: self::modelDescription($model, $event),
            auditable: $model,
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }

    public static function logAction(string $description, ?Model $subject = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        if (! self::shouldAudit()) {
            return;
        }

        self::write(
            event: 'action',
            description: $description,
            auditable: $subject,
            oldValues: $oldValues ? self::filterAttributes($oldValues) : null,
            newValues: $newValues ? self::filterAttributes($newValues) : null,
        );
    }

    private static function write(
        string $event,
        string $description,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        $user = auth()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $user?->role,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    private static function modelDescription(Model $model, string $event): string
    {
        $type = class_basename($model);
        $id = $model->getKey();

        $label = match ($type) {
            'User' => trim(($model->first_name ?? '').' '.($model->last_name ?? '')) ?: ($model->email ?? "User #{$id}"),
            'Course' => $model->name ?? "Course #{$id}",
            'TrainingApplication' => $model->registration_number ?? $model->control_number ?? "Application #{$id}",
            'AttendanceSession' => $model->name ?? "Session #{$id}",
            default => "{$type} #{$id}",
        };

        return match ($event) {
            'created' => __('Created :type: :label', ['type' => $type, 'label' => $label]),
            'updated' => __('Updated :type: :label', ['type' => $type, 'label' => $label]),
            'deleted' => __('Deleted :type: :label', ['type' => $type, 'label' => $label]),
            default => __('Changed :type: :label', ['type' => $type, 'label' => $label]),
        };
    }

    private static function extractOldValues(Model $model, array $keys): array
    {
        $original = $model->getOriginal();
        $old = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $original)) {
                $old[$key] = $original[$key];
            }
        }

        return self::filterAttributes($old);
    }

    private static function filterAttributes(array $attributes): array
    {
        $filtered = [];
        foreach ($attributes as $key => $value) {
            if (in_array($key, self::HIDDEN_ATTRIBUTES, true)) {
                $filtered[$key] = '[hidden]';
                continue;
            }
            if (is_array($value) || is_object($value)) {
                $filtered[$key] = json_encode($value);
            } elseif ($value instanceof \DateTimeInterface) {
                $filtered[$key] = $value->format('Y-m-d H:i:s');
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}
