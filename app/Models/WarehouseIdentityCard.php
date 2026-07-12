<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseIdentityCard extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'training_application_id',
        'user_id',
        'verification_token',
        'registration_number',
        'session_year',
        'trained_year',
        'full_name',
        'position',
        'course_name',
        'company_name',
        'photo_path',
        'pdf_path',
        'status',
        'issued_at',
        'expires_at',
        'generated_by',
        'generated_at',
        'published_by',
        'published_at',
        'revoked_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'session_year' => 'integer',
            'trained_year' => 'integer',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function trainingApplication(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function isValid(): bool
    {
        return $this->isPublished() && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => $this->isExpired() ? __('Published (expired)') : __('Published'),
            self::STATUS_REVOKED => __('Revoked'),
            default => __('Draft'),
        };
    }

    public function verificationUrl(): string
    {
        return route('identity-cards.verify', $this->verification_token);
    }

    public static function generateVerificationToken(): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (self::where('verification_token', $token)->exists());

        return $token;
    }
}
