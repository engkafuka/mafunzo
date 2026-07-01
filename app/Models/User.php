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
        ];
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
