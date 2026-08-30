<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewSessionPanelist extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'is_chair',
        'submission_status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_chair' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
