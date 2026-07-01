<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationBackground extends Model
{
    protected $fillable = [
        'user_id',
        'level',
        'program',
        'program_other',
        'institution',
        'certificate_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function levelOptions(): array
    {
        return [
            'certificate' => 'Certificate',
            'diploma' => 'Diploma',
            'degree' => 'Degree',
            'secondary' => 'Secondary education',
        ];
    }

    public static function programOptions(): array
    {
        return [
            'agriculture' => 'Agriculture',
            'others' => 'Others',
        ];
    }
}
