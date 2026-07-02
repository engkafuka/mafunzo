<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
        ];
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

    public function educationBackgrounds()
    {
        return $this->hasMany(EducationBackground::class);
    }

    public function hasCompletedTraineeProfile(): bool
    {
        if ($this->role !== 'trainee') {
            return true;
        }
        if ($this->profile_completed_at) {
            return true;
        }
        return $this->educationBackgrounds()->whereNotNull('certificate_path')->exists();
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
}
