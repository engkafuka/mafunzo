<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttendanceSession extends Model
{
    protected $fillable = ['course_id', 'name', 'session_date', 'qr_token'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public static function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('qr_token', $token)->exists());
        return $token;
    }
}
