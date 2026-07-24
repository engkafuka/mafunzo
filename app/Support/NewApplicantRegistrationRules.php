<?php

namespace App\Support;

use App\Models\EducationBackground;
use App\Models\TrainingApplication;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NewApplicantRegistrationRules
{
    public static function personalRules(?int $ignoreUserId = null): array
    {
        $emailRule = ValidationRules::registrationEmail($ignoreUserId);

        return array_merge([
            'first_name' => ValidationRules::personName(),
            'middle_name' => ValidationRules::personName(),
            'last_name' => ValidationRules::personName(),
            'email' => $emailRule,
            'phone' => ValidationRules::phone(),
            'gender' => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'position' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingApplication::positionOptions()))],
            'company_or_private' => ['required', 'in:company,private'],
            'company_name' => ['required_if:company_or_private,company', 'nullable', 'string', 'max:255'],
            'company_address' => ['required_if:company_or_private,company', 'nullable', 'string', 'max:500'],
        ], TanzaniaLocations::validationRules());
    }

    public static function locationRules(): array
    {
        return TanzaniaLocations::validationRules();
    }

    public static function educationRules(bool $certificatesRequired = true, bool $includeWrrb = false): array
    {
        $certificateRules = $certificatesRequired
            ? ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']
            : ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        return [
            'education' => ['required', 'array', 'min:1'],
            'education.*.id' => ['nullable', 'integer', 'exists:education_backgrounds,id'],
            'education.*.level' => ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::levelOptions($includeWrrb)))],
            'education.*.program' => ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::programOptions()))],
            'education.*.program_other' => ['nullable', 'string', 'max:255'],
            'education.*.institution' => ['required', 'string', 'max:255'],
            'education.*.certificate' => $certificateRules,
        ];
    }

    public static function validateEducationRows(
        Request $request,
        bool $certificatesRequired = true,
        bool $requireWrrbCertificate = false,
    ): void {
        $errors = [];
        $educationRows = $request->input('education', []);
        $hasWrrbRow = false;

        foreach ($educationRows as $index => $education) {
            $level = $education['level'] ?? '';

            if ($level === EducationBackground::LEVEL_WRRB_CERTIFICATE) {
                $hasWrrbRow = true;
            }

            if (($education['program'] ?? '') === 'others' && blank($education['program_other'] ?? null)) {
                $errors["education.$index.program_other"] = __('The program specification field is required.');
            }

            $hasExisting = ! empty($education['id']);
            $hasFile = $request->hasFile("education.$index.certificate");

            if ($certificatesRequired && ! $hasFile) {
                $errors["education.$index.certificate"] = __('The education certificate field is required.');
            } elseif (! $certificatesRequired && ! $hasExisting && ! $hasFile) {
                $errors["education.$index.certificate"] = __('The education certificate field is required.');
            }
        }

        if ($requireWrrbCertificate && ! $hasWrrbRow) {
            $errors['education'] = __('Include at least one education entry with level “WRRB Certificate” and upload the certificate.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function attributeNames(): array
    {
        return [
            'first_name' => __('first name'),
            'middle_name' => __('middle name'),
            'last_name' => __('last name'),
            'email' => __('email'),
            'phone' => __('phone number'),
            'region' => __('region'),
            'district' => __('district'),
            'gender' => __('gender'),
            'date_of_birth' => __('date of birth'),
            'position' => __('position'),
            'company_or_private' => __('company / private'),
            'company_name' => __('company name'),
            'company_address' => __('company address'),
            'education' => __('education background'),
            'education.*.level' => __('education level'),
            'education.*.program' => __('education program'),
            'education.*.program_other' => __('program specification'),
            'education.*.institution' => __('institution'),
            'education.*.certificate' => __('education certificate'),
        ];
    }
}
