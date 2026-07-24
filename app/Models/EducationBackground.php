<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationBackground extends Model
{
    public const LEVEL_WRRB_CERTIFICATE = 'wrrb_certificate';

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

    public static function levelOptions(bool $includeWrrb = false): array
    {
        $options = [
            'certificate' => 'Certificate',
            'diploma' => 'Diploma',
            'degree' => 'Degree',
            'secondary' => 'Secondary education',
        ];

        if ($includeWrrb) {
            $options[self::LEVEL_WRRB_CERTIFICATE] = 'WRRB Certificate';
        }

        return $options;
    }

    public static function levelLabel(?string $level): string
    {
        $options = self::levelOptions(includeWrrb: true);

        return __($options[$level] ?? ($level ?: '—'));
    }

    public static function programOptions(): array
    {
        return [
            'agriculture' => 'Agriculture',
            'others' => 'Others',
        ];
    }

    public function isWrrbCertificate(): bool
    {
        return $this->level === self::LEVEL_WRRB_CERTIFICATE;
    }
}
