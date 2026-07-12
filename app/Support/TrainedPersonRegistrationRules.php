<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrainedPersonRegistrationRules
{
    public static function trainingRules(bool $certificatesRequired = true): array
    {
        $certificateRules = $certificatesRequired
            ? ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']
            : ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        return [
            'course_id' => ['required', 'exists:courses,id'],
            'trained_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'certificate_number' => ['required', 'string', 'max:100'],
            'training_certificate' => $certificateRules,
        ];
    }

    public static function validateTrainingCertificate(Request $request, bool $hasExistingCertificate): void
    {
        if (! $hasExistingCertificate && ! $request->hasFile('training_certificate')) {
            throw ValidationException::withMessages([
                'training_certificate' => __('The training certificate field is required.'),
            ]);
        }
    }

    public static function attributeNames(): array
    {
        return [
            'course_id' => __('course trained'),
            'trained_year' => __('year trained'),
            'certificate_number' => __('certificate number'),
            'training_certificate' => __('training certificate'),
        ];
    }
}
