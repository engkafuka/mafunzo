<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\EducationBackground;
use App\Models\TrainingApplication;
use App\Models\User;
use App\Support\ValidationRules;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $courses = Course::orderByDesc('session_year')->orderBy('name')->get();

        return view('auth.register', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'registration_category' => ['required', 'in:new_applicant,trained_person'],
        ], ValidationRules::requiredMessages(), [
            'registration_category' => __('registration category'),
        ]);

        $category = $request->input('registration_category');
        $rules = $this->baseRules($category);
        $attributes = $this->attributeNames();

        $validated = $request->validate($rules, ValidationRules::requiredMessages(), $attributes);

        $user = DB::transaction(function () use ($request, $validated, $category) {
            $name = trim($validated['first_name'].' '.$validated['middle_name'].' '.$validated['last_name']);

            $user = User::create([
                'name' => $name,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'trainee',
                'registration_category' => $category,
                'registration_status' => 'pending',
                'region' => $validated['region'],
                'district' => $validated['district'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'position' => $validated['position'],
                'company_or_private' => $validated['company_or_private'],
                'company_name' => $validated['company_or_private'] === 'company' ? $validated['company_name'] : null,
                'company_address' => $validated['company_or_private'] === 'company' ? $validated['company_address'] : null,
                'profile_completed_at' => $category === 'new_applicant' ? now() : null,
            ]);

            if ($category === 'new_applicant') {
                $educationPath = $request->file('education_certificate')->store('certificates', 'local');

                EducationBackground::create([
                    'user_id' => $user->id,
                    'level' => $validated['education_level'],
                    'program' => $validated['education_program'],
                    'program_other' => $validated['education_program'] === 'others' ? $validated['education_program_other'] : null,
                    'institution' => $validated['education_institution'],
                    'certificate_path' => $educationPath,
                ]);
            }

            if ($category === 'trained_person') {
                $certificatePath = $request->file('training_certificate')->store('certificates/legacy', 'local');

                TrainingApplication::create([
                    'user_id' => $user->id,
                    'course_id' => $validated['course_id'],
                    'application_type' => 'legacy_expert',
                    'trained_year' => $validated['trained_year'],
                    'legacy_registration_number' => $validated['legacy_registration_number'],
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'region' => $validated['region'],
                    'district' => $validated['district'],
                    'company_or_private' => $validated['company_or_private'],
                    'company_name' => $validated['company_or_private'] === 'company' ? $validated['company_name'] : null,
                    'company_address' => $validated['company_or_private'] === 'company' ? $validated['company_address'] : null,
                    'gender' => $validated['gender'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'position' => $validated['position'],
                    'status' => 'pending_registration',
                    'application_review_status' => 'pending',
                    'certificate_path' => $certificatePath,
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('registration.pending');
    }

    private function baseRules(string $category): array
    {
        $rules = [
            'registration_category' => ['required', 'in:new_applicant,trained_person'],
            'first_name' => ValidationRules::personName(),
            'middle_name' => ValidationRules::personName(),
            'last_name' => ValidationRules::personName(),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ValidationRules::phone(),
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'region' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'position' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingApplication::positionOptions()))],
            'company_or_private' => ['required', 'in:company,private'],
            'company_name' => ['required_if:company_or_private,company', 'string', 'max:255'],
            'company_address' => ['required_if:company_or_private,company', 'string', 'max:500'],
        ];

        if ($category === 'new_applicant') {
            $rules['education_level'] = ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::levelOptions()))];
            $rules['education_program'] = ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::programOptions()))];
            $rules['education_program_other'] = ['required_if:education_program,others', 'string', 'max:255'];
            $rules['education_institution'] = ['required', 'string', 'max:255'];
            $rules['education_certificate'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        if ($category === 'trained_person') {
            $rules['course_id'] = ['required', 'exists:courses,id'];
            $rules['trained_year'] = ['required', 'integer', 'min:2000', 'max:2100'];
            $rules['legacy_registration_number'] = ['required', 'string', 'max:100'];
            $rules['training_certificate'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        return $rules;
    }

    private function attributeNames(): array
    {
        return [
            'registration_category' => __('registration category'),
            'first_name' => __('first name'),
            'middle_name' => __('middle name'),
            'last_name' => __('last name'),
            'email' => __('email'),
            'phone' => __('phone number'),
            'password' => __('password'),
            'region' => __('region'),
            'district' => __('district'),
            'gender' => __('gender'),
            'date_of_birth' => __('date of birth'),
            'position' => __('position'),
            'company_or_private' => __('company / private'),
            'company_name' => __('company name'),
            'company_address' => __('company address'),
            'education_level' => __('education level'),
            'education_program' => __('education program'),
            'education_program_other' => __('program specification'),
            'education_institution' => __('institution'),
            'education_certificate' => __('education certificate'),
            'course_id' => __('course trained'),
            'trained_year' => __('year trained'),
            'legacy_registration_number' => __('previous registration number'),
            'training_certificate' => __('training certificate'),
        ];
    }
}
