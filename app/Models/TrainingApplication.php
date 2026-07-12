<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TrainingApplication extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id',
        'course_id',
        'application_type',
        'trained_year',
        'certificate_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'region',
        'district',
        'company_or_private',
        'company_name',
        'company_address',
        'gender',
        'date_of_birth',
        'position',
        'control_number',
        'payment_completed_at',
        'registration_number',
        'status',
        'application_review_status',
        'application_reviewed_at',
        'account_verified_at',
        'payment_verified_at',
        'exam_score',
        'exam_passed',
        'exam_result_path',
        'exam_uploaded_at',
        'exam_results_published_at',
        'certificate_issued_at',
        'certificate_path',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'trained_year' => 'integer',
            'payment_completed_at' => 'datetime',
            'application_reviewed_at' => 'datetime',
            'account_verified_at' => 'datetime',
            'payment_verified_at' => 'datetime',
            'exam_passed' => 'boolean',
            'exam_uploaded_at' => 'datetime',
            'exam_results_published_at' => 'datetime',
            'certificate_issued_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isLegacyExpert(): bool
    {
        return $this->application_type === 'legacy_expert';
    }

    public function warehouseIdentityCard()
    {
        return $this->hasOne(WarehouseIdentityCard::class);
    }

    /**
     * Open application: payment pending or in progress, not rejected, exam not yet published.
     * Used to enforce one training at a time per trainee.
     */
    public function isOpen(): bool
    {
        if (! in_array($this->status, ['pending_payment', 'payment_completed'], true)) {
            return false;
        }

        if ($this->application_review_status === 'rejected') {
            return false;
        }

        if ($this->hasPublishedExamResults()) {
            return false;
        }

        return true;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->whereIn('status', ['pending_payment', 'payment_completed'])
            ->where(function ($q) {
                $q->whereNull('application_review_status')
                    ->orWhere('application_review_status', '!=', 'rejected');
            })
            ->whereNull('exam_results_published_at');
    }

    public function isEligibleForIdentityCard(): bool
    {
        return $this->identityCardIneligibilityReason() === null;
    }

    public function identityCardIneligibilityReason(): ?string
    {
        foreach ($this->identityCardEligibilityChecklist() as $item) {
            if (! $item['met']) {
                return $item['label'];
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, label: string, met: bool}>
     */
    public function identityCardEligibilityChecklist(): array
    {
        $this->loadMissing('user');

        return [
            [
                'key' => 'registration_number',
                'label' => __('Official WRRB registration number'),
                'met' => filled($this->registration_number),
            ],
            [
                'key' => 'payment_completed',
                'label' => __('Payment completed'),
                'met' => $this->status === 'payment_completed',
            ],
            [
                'key' => 'application_approved',
                'label' => __('Application approved'),
                'met' => $this->application_review_status === 'approved',
            ],
            [
                'key' => 'account_verified',
                'label' => __('Account verified by staff'),
                'met' => $this->account_verified_at !== null,
            ],
            [
                'key' => 'payment_verified',
                'label' => __('Payment verified by staff'),
                'met' => $this->payment_verified_at !== null,
            ],
            [
                'key' => 'exam_passed',
                'label' => __('Examination passed'),
                'met' => $this->exam_passed === true,
            ],
            [
                'key' => 'exam_published',
                'label' => __('Examination results published'),
                'met' => $this->hasPublishedExamResults(),
            ],
            [
                'key' => 'profile_photo',
                'label' => __('Profile photo uploaded'),
                'met' => filled($this->user?->profile_photo_path),
            ],
        ];
    }

    public function hasPublishedIdentityCard(): bool
    {
        return $this->warehouseIdentityCard?->isPublished() ?? false;
    }

    public function isEligibleForCertificate(): bool
    {
        return $this->status === 'payment_completed'
            && $this->application_review_status === 'approved'
            && $this->account_verified_at !== null
            && $this->payment_verified_at !== null
            && $this->exam_passed === true;
    }

    public function hasPublishedExamResults(): bool
    {
        return $this->exam_results_published_at !== null;
    }

    public function hasRecordedExamResults(): bool
    {
        return $this->exam_uploaded_at !== null;
    }

    public function isAwaitingExamResultsPublication(): bool
    {
        return $this->hasRecordedExamResults() && ! $this->hasPublishedExamResults();
    }

    public function examResultStatusLabel(): string
    {
        if (! $this->hasRecordedExamResults()) {
            return __('Awaiting results');
        }

        if (! $this->hasPublishedExamResults()) {
            return __('Awaiting publication');
        }

        if ($this->exam_passed === true) {
            return __('Passed');
        }

        if ($this->exam_passed === false) {
            return __('Not passed');
        }

        return __('Results published');
    }

    public function examPublicationStatusLabel(): string
    {
        if ($this->hasPublishedExamResults()) {
            return __('Published');
        }

        if ($this->hasRecordedExamResults()) {
            return __('Saved — awaiting publish');
        }

        return __('Not recorded');
    }

    public function isStaffActionable(): bool
    {
        return $this->status !== 'pending_registration';
    }

    public function needsAccountVerification(): bool
    {
        return $this->isStaffActionable() && $this->account_verified_at === null;
    }

    public function needsPaymentVerification(): bool
    {
        return $this->isStaffActionable()
            && $this->payment_verified_at === null
            && in_array($this->status, ['pending_payment', 'payment_completed'], true);
    }

    public function canBeReviewedByStaff(): bool
    {
        return $this->status === 'payment_completed'
            && $this->application_review_status === 'pending';
    }

    public static function positionOptions(): array
    {
        return [
            'quality_assurance' => 'Quality Assurance',
            'manager' => 'Manager',
            'weight_assistant' => 'Weight Assistant',
            'documentation' => 'Documentation',
            'store_keeper' => 'Store Keeper',
            'collateral_manager' => 'Collateral Manager',
            'managing_director' => 'Managing Director',
        ];
    }

    public static function positionLabel(?string $position): ?string
    {
        if ($position === null || $position === '') {
            return null;
        }

        $options = self::positionOptions();

        if (isset($options[$position])) {
            return $options[$position];
        }

        if ($position === 'other') {
            return 'Managing Director';
        }

        return str_replace('_', ' ', ucwords($position, '_'));
    }

    /**
     * Official WRRB series format: WRRB/YYYY/1/XXXX
     */
    public static function isOfficialRegistrationNumber(?string $number): bool
    {
        return is_string($number) && preg_match('/^WRRB\/\d{4}\/1\/\d{4}$/', $number) === 1;
    }

    /**
     * Resolve the registration number for an application, reusing an official number or generating the next in series.
     */
    public static function registrationNumberFor(self $application): string
    {
        if (static::isOfficialRegistrationNumber($application->registration_number)) {
            return $application->registration_number;
        }

        return static::generateRegistrationNumber();
    }

    /**
     * Next unique registration number in the shared WRRB series (WRRB/YYYY/1/XXXX).
     */
    public static function generateRegistrationNumber(): string
    {
        return DB::transaction(function () {
            // Serialize concurrent generators so legacy and new applicants share one sequence.
            static::query()->select('id')->orderBy('id')->limit(1)->lockForUpdate()->first();

            $year = date('Y');
            $prefix = "WRRB/{$year}/1/";

            $maxSeq = static::query()
                ->where('registration_number', 'like', $prefix.'%')
                ->pluck('registration_number')
                ->map(fn (string $number) => (int) substr($number, -4))
                ->max() ?? 0;

            do {
                $maxSeq++;
                $candidate = sprintf('WRRB/%s/1/%04d', $year, $maxSeq);
            } while (static::where('registration_number', $candidate)->exists());

            return $candidate;
        });
    }
}
