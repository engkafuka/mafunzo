<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const REQUESTED_BY_TRAINEE = 'trainee';

    public const REQUESTED_BY_COMPANY = 'company';

    public const TYPE_UPDATE = 'update';

    public const TYPE_RELEASE = 'release';

    protected $fillable = [
        'license_nomination_id',
        'requested_by_user_id',
        'requested_by',
        'change_type',
        'current_final_position',
        'proposed_final_position',
        'current_organization_name',
        'proposed_organization_name',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function nomination(): BelongsTo
    {
        return $this->belongsTo(LicenseNomination::class, 'license_nomination_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function proposedPositionLabel(): ?string
    {
        if ($this->proposed_final_position === null) {
            return null;
        }

        return TrainingApplication::positionLabel($this->proposed_final_position)
            ?? $this->proposed_final_position;
    }

    public function currentPositionLabel(): ?string
    {
        if ($this->current_final_position === null) {
            return null;
        }

        return TrainingApplication::positionLabel($this->current_final_position)
            ?? $this->current_final_position;
    }

    public function summaryLabel(): string
    {
        if ($this->change_type === self::TYPE_RELEASE) {
            return __('Release from company');
        }

        $parts = [];
        if ($this->proposed_final_position !== null
            && $this->proposed_final_position !== $this->current_final_position
        ) {
            $parts[] = __('Position: :from → :to', [
                'from' => $this->currentPositionLabel() ?? '—',
                'to' => $this->proposedPositionLabel() ?? '—',
            ]);
        }
        if ($this->proposed_organization_name !== null
            && $this->proposed_organization_name !== $this->current_organization_name
        ) {
            $parts[] = __('Organization: :from → :to', [
                'from' => $this->current_organization_name ?: '—',
                'to' => $this->proposed_organization_name ?: '—',
            ]);
        }

        return $parts !== [] ? implode('; ', $parts) : __('Update engagement');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'license_nomination_id' => $this->license_nomination_id,
            'requested_by' => $this->requested_by,
            'change_type' => $this->change_type,
            'current_final_position' => $this->current_final_position,
            'proposed_final_position' => $this->proposed_final_position,
            'current_organization_name' => $this->current_organization_name,
            'proposed_organization_name' => $this->proposed_organization_name,
            'reason' => $this->reason,
            'status' => $this->status,
            'review_notes' => $this->review_notes,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'summary' => $this->summaryLabel(),
        ];
    }
}
