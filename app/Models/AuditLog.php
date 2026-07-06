<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'event',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subjectLabel(): string
    {
        if ($this->auditable_type) {
            return class_basename($this->auditable_type).' #'.$this->auditable_id;
        }

        return '—';
    }

    public static function eventOptions(): array
    {
        return [
            'created' => __('Created'),
            'updated' => __('Updated'),
            'deleted' => __('Deleted'),
            'action' => __('Action'),
        ];
    }

    public function eventLabel(): string
    {
        return self::eventOptions()[$this->event] ?? ucfirst($this->event);
    }
}
