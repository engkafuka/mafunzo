<?php

namespace App\Models\Concerns;

use App\Support\AuditLogger;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (self $model) {
            AuditLogger::logModelEvent($model, 'created');
        });

        static::updated(function (self $model) {
            AuditLogger::logModelEvent($model, 'updated');
        });

        static::deleted(function (self $model) {
            AuditLogger::logModelEvent($model, 'deleted');
        });
    }
}
