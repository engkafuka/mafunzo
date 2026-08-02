<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseNomination extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_LICENSED = 'licensed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'user_id',
        'training_application_id',
        'registration_number',
        'final_position',
        'licensing_application_id',
        'organization_name',
        'license_number',
        'callback_url',
        'status',
        'expires_at',
        'responded_at',
        'license_issued_at',
        'license_valid_from',
        'license_valid_until',
        'callback_sent_at',
        'callback_error',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
            'license_issued_at' => 'datetime',
            'license_valid_from' => 'date',
            'license_valid_until' => 'date',
            'callback_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingApplication(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(LicenseChangeRequest::class);
    }

    public function allowsChangeRequests(): bool
    {
        if ($this->status === self::STATUS_ACCEPTED) {
            return true;
        }

        return $this->isLicenseActive();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->status === self::STATUS_PENDING
                && $this->expires_at !== null
                && $this->expires_at->isPast());
    }

    /**
     * Accepted (awaiting license) or currently within an active license period.
     */
    public function isReserved(): bool
    {
        if ($this->status === self::STATUS_ACCEPTED) {
            return true;
        }

        return $this->isLicenseActive();
    }

    public function isLicenseActive(): bool
    {
        if ($this->status !== self::STATUS_LICENSED) {
            return false;
        }

        if ($this->license_valid_until === null) {
            return true;
        }

        return $this->license_valid_until->copy()->endOfDay()->isFuture()
            || $this->license_valid_until->isToday();
    }

    public function finalPositionLabel(): string
    {
        return TrainingApplication::positionLabel($this->final_position)
            ?? (string) $this->final_position;
    }

    /**
     * Users currently reserved by accept or active license.
     *
     * @return Builder<static>
     */
    public static function reservedQuery(): Builder
    {
        return static::query()->where(function ($q) {
            $q->where('status', self::STATUS_ACCEPTED)
                ->orWhere(function ($licensed) {
                    $licensed->where('status', self::STATUS_LICENSED)
                        ->where(function ($validity) {
                            $validity->whereNull('license_valid_until')
                                ->orWhereDate('license_valid_until', '>=', now()->toDateString());
                        });
                });
        });
    }

    public static function userIsReserved(?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return static::reservedQuery()->where('user_id', $userId)->exists();
    }

    public static function registrationIsReserved(string $registrationNumber): bool
    {
        return static::reservedQuery()
            ->where('registration_number', $registrationNumber)
            ->exists();
    }

    public function toApiArray(): array
    {
        $status = $this->status;
        if ($this->isExpired() && $this->status === self::STATUS_PENDING) {
            $status = self::STATUS_EXPIRED;
        }

        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'final_position' => $this->final_position,
            'final_position_label' => $this->finalPositionLabel(),
            'licensing_application_id' => $this->licensing_application_id,
            'organization_name' => $this->organization_name,
            'status' => $status,
            'is_reserved' => $this->isReserved(),
            'license_number' => $this->license_number,
            'license_issued_at' => $this->license_issued_at?->toIso8601String(),
            'license_valid_from' => $this->license_valid_from?->toDateString(),
            'license_valid_until' => $this->license_valid_until?->toDateString(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
