<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewCompany extends Model
{
    protected $fillable = [
        'name',
        'registration_number',
        'contact_person',
        'contact_email',
        'contact_phone',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (InterviewCompany $company) {
            if ($company->name !== null) {
                $company->name = self::normalizeName($company->name);
            }

            if ($company->registration_number !== null) {
                $company->registration_number = self::normalizeRegistrationNumber($company->registration_number);
            }
        });
    }

    public static function normalizeName(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }

    public static function normalizeRegistrationNumber(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(InterviewSession::class, 'company_id');
    }

    public function canBeDeleted(): bool
    {
        if (isset($this->sessions_count)) {
            return (int) $this->sessions_count === 0;
        }

        return ! $this->sessions()->exists();
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }
}
