<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingApplication extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
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
        'certificate_issued_at',
        'certificate_path',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'payment_completed_at' => 'datetime',
            'application_reviewed_at' => 'datetime',
            'account_verified_at' => 'datetime',
            'payment_verified_at' => 'datetime',
            'exam_passed' => 'boolean',
            'exam_uploaded_at' => 'datetime',
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

    public function isEligibleForCertificate(): bool
    {
        return $this->status === 'payment_completed'
            && $this->application_review_status === 'approved'
            && $this->account_verified_at !== null
            && $this->payment_verified_at !== null
            && $this->exam_passed === true;
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
            'other' => 'Other',
        ];
    }
}
