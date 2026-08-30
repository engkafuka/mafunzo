<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InterviewSession extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SCORING = 'scoring';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'session_code',
        'company_id',
        'question_set_id',
        'interviewee_name',
        'interviewee_title',
        'interview_type',
        'interview_date',
        'interview_time',
        'venue',
        'pass_mark',
        'status',
        'notes',
        'created_by',
        'scoring_opened_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'interview_date' => 'date',
            'pass_mark' => 'decimal:2',
            'scoring_opened_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(InterviewCompany::class, 'company_id');
    }

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestionSet::class, 'question_set_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function panelists(): HasMany
    {
        return $this->hasMany(InterviewSessionPanelist::class, 'session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(InterviewScore::class, 'session_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(InterviewResult::class, 'session_id');
    }

    public function chairAssignment(): ?InterviewSessionPanelist
    {
        return $this->panelists()->where('is_chair', true)->first();
    }

    public function allPanelistsSubmitted(): bool
    {
        $count = $this->panelists()->count();

        if ($count === 0) {
            return false;
        }

        return $this->panelists()->where('submission_status', 'submitted')->count() === $count;
    }

    public function isLockedForScoring(User $user): bool
    {
        $assignment = $this->panelists()->where('user_id', $user->id)->first();

        return $assignment && $assignment->submission_status === 'submitted';
    }

    public function statusLabel(): string
    {
        return config('interview.session_statuses')[$this->status] ?? $this->status;
    }

    public function typeLabel(): string
    {
        return config('interview.interview_types')[$this->interview_type] ?? $this->interview_type;
    }
}
