<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ValidationRules
{
    public static function registrationEmail(?int $ignoreUserId = null): array
    {
        $unique = $ignoreUserId
            ? Rule::unique(User::class, 'email')->ignore($ignoreUserId)
            : Rule::unique(User::class, 'email');

        return ['required', 'string', 'lowercase', 'email', 'max:255', $unique];
    }

    public static function password(bool $required = true): array
    {
        $rules = ['string', Password::defaults(), 'confirmed'];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function passwordRequirementsDescription(): string
    {
        $requirements = __('At least 6 characters, with uppercase and lowercase letters, a number, and a symbol.');

        if (! app()->environment('local', 'testing')) {
            $requirements .= ' '.__('Must not be a commonly used or compromised password.');
        }

        return $requirements;
    }

    public static function phone(): array
    {
        return ['required', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'];
    }

    public static function personName(bool $required = true): array
    {
        $rules = ['string', 'max:255'];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function requiredMessages(): array
    {
        return [
            'required' => __('The :attribute field is required.'),
            'email' => __('The :attribute must be a valid email address.'),
            'confirmed' => __('The :attribute confirmation does not match.'),
            'required_if' => __('The :attribute field is required.'),
            'after_or_equal' => __('The :attribute must be on or after :date.'),
            'before' => __('The :attribute must be a date before today.'),
            'before_or_equal' => __('The :attribute must be on or before :date.'),
            'in' => __('The selected :attribute is invalid.'),
            'unique' => __('The :attribute has already been taken.'),
            'regex' => __('The :attribute format is invalid.'),
            'file' => __('The :attribute must be a file.'),
            'mimes' => __('The :attribute must be a file of type: :values.'),
            'max.file' => __('The :attribute must not be greater than :max kilobytes.'),
        ];
    }

    public static function registrationMessages(): array
    {
        return array_merge(self::requiredMessages(), [
            'email.unique' => __('This email address is already registered. Each applicant may register only once. Please log in with your existing account.'),
        ]);
    }
}
