<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewResult extends Model
{
    protected $fillable = [
        'session_id',
        'total_score',
        'max_possible_score',
        'percentage',
        'passed',
        'recommendation',
        'chair_notes',
        'decision_status',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'calculation_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'max_possible_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'passed' => 'boolean',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'calculation_snapshot' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
