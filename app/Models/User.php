<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'registration_category',
        'registration_status',
        'registration_reviewed_at',
        'registration_reviewed_by',
        'registration_rejection_reason',
        'region',
        'district',
        'gender',
        'date_of_birth',
        'position',
        'company_or_private',
        'company_name',
        'company_address',
        'profile_photo_path',
        'profile_photo_uploaded_at',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_completed_at' => 'datetime',
            'registration_reviewed_at' => 'datetime',
            'date_of_birth' => 'date',
            'profile_photo_uploaded_at' => 'datetime',
        ];
    }

    public function hasProfilePhoto(): bool
    {
        return filled($this->profile_photo_path);
    }

    public function warehouseIdentityCards()
    {
        return $this->hasMany(WarehouseIdentityCard::class);
    }

    public function registrationReviewer()
    {
        return $this->belongsTo(User::class, 'registration_reviewed_by');
    }

    public static function registrationCategoryOptions(): array
    {
        return [
            'new_applicant' => __('New applicant'),
            'trained_person' => __('Previously trained person'),
        ];
    }

    public function isNewApplicant(): bool
    {
        return $this->registration_category === 'new_applicant';
    }

    public function isTrainedPerson(): bool
    {
        return $this->registration_category === 'trained_person';
    }

    public function hasApprovedRegistration(): bool
    {
        return $this->role !== 'trainee' || $this->registration_status === 'approved';
    }

    public function hasPendingRegistration(): bool
    {
        return $this->role === 'trainee' && $this->registration_status === 'pending';
    }

    public function hasRejectedRegistration(): bool
    {
        return $this->role === 'trainee' && $this->registration_status === 'rejected';
    }

    public function canResubmitRegistration(): bool
    {
        if ($this->role !== 'trainee' || $this->hasApprovedRegistration()) {
            return false;
        }

        return $this->hasRejectedRegistration() || filled($this->registration_rejection_reason);
    }

    public function educationBackgrounds()
    {
        return $this->hasMany(EducationBackground::class);
    }

    public function hasCompletedTraineeProfile(): bool
    {
        if ($this->role !== 'trainee') {
            return true;
        }

        return $this->isReadyToApplyForCourse();
    }

    /**
     * Personal profile fields required before applying for a course.
     *
     * @return list<string> Human-readable missing field labels
     */
    public function missingFieldsForCourseApplication(): array
    {
        if ($this->role !== 'trainee') {
            return [];
        }

        $missing = [];

        $required = [
            'first_name' => __('first name'),
            'last_name' => __('last name'),
            'email' => __('email'),
            'phone' => __('phone number'),
            'region' => __('region'),
            'district' => __('district'),
            'gender' => __('gender'),
            'date_of_birth' => __('date of birth'),
            'position' => __('position'),
            'company_or_private' => __('company / private'),
        ];

        foreach ($required as $field => $label) {
            if (! filled($this->{$field})) {
                $missing[] = $label;
            }
        }

        if ($this->company_or_private === 'company') {
            if (! filled($this->company_name)) {
                $missing[] = __('company name');
            }
            if (! filled($this->company_address)) {
                $missing[] = __('company address');
            }
        }

        if ($this->position && ! array_key_exists($this->position, TrainingApplication::positionOptions())) {
            $missing[] = __('valid position');
        }

        if ($this->isNewApplicant() && ! $this->educationBackgrounds()->whereNotNull('certificate_path')->exists()) {
            $missing[] = __('education background with certificate');
        }

        if (! $this->hasProfilePhoto()) {
            $missing[] = __('profile photo');
        }

        return $missing;
    }

    public function isReadyToApplyForCourse(): bool
    {
        return $this->missingFieldsForCourseApplication() === [];
    }

    /**
     * Copy current user profile into a new training application snapshot.
     *
     * @return array<string, mixed>
     */
    public function applicationSnapshotAttributes(): array
    {
        $isCompany = $this->company_or_private === 'company';

        return [
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'region' => $this->region,
            'district' => $this->district,
            'company_or_private' => $this->company_or_private,
            'company_name' => $isCompany ? $this->company_name : null,
            'company_address' => $isCompany ? $this->company_address : null,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'position' => $this->position,
        ];
    }

    public function trainingApplications()
    {
        return $this->hasMany(TrainingApplication::class);
    }

    /** Roles that can be assigned by admins (admin cannot create super_admin or admin). */
    public static function rolesAssignableByAdmin(): array
    {
        return [
            'trainer' => 'Trainer',
            'staff' => 'Staff',
            'trainee' => 'Trainee',
        ];
    }

    /** All roles that super_admin can assign. */
    public static function rolesAssignableBySuperAdmin(): array
    {
        return [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'trainer' => 'Trainer',
            'staff' => 'Staff',
            'trainee' => 'Trainee',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAdminOrSuperAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin'], true);
    }

    public function isTrainer(): bool
    {
        return $this->role === 'trainer';
    }

    public function canManageExamResults(): bool
    {
        return in_array($this->role, ['super_admin', 'admin', 'staff', 'trainer'], true);
    }
}
