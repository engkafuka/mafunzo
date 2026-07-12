<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\EducationBackground;
use App\Models\TrainingApplication;
use App\Models\User;
use App\Support\NewApplicantRegistrationRules;
use App\Support\ProfilePhotoStorage;
use App\Support\ValidationRules;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        $validated = $request->validate($rules, ValidationRules::registrationMessages(), $attributes);

        if ($category === 'new_applicant') {
            NewApplicantRegistrationRules::validateEducationRows($request);
        }

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
                foreach ($validated['education'] as $index => $education) {
                    $educationPath = $request->file("education.$index.certificate")->store('certificates', 'local');

                    EducationBackground::create([
                        'user_id' => $user->id,
                        'level' => $education['level'],
                        'program' => $education['program'],
                        'program_other' => $education['program'] === 'others' ? ($education['program_other'] ?? null) : null,
                        'institution' => $education['institution'],
                        'certificate_path' => $educationPath,
                    ]);
                }
            }

            if ($category === 'trained_person') {
                $certificatePath = $request->file('training_certificate')->store('certificates/legacy', 'local');

                TrainingApplication::create([
                    'user_id' => $user->id,
                    'course_id' => $validated['course_id'],
                    'application_type' => 'legacy_expert',
                    'trained_year' => $validated['trained_year'],
                    'certificate_number' => $validated['certificate_number'],
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

            if ($request->hasFile('profile_photo')) {
                $photoPath = ProfilePhotoStorage::storeForUser($user, $request->file('profile_photo'));
                $user->update([
                    'profile_photo_path' => $photoPath,
                    'profile_photo_uploaded_at' => now(),
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
            'email' => ValidationRules::registrationEmail(),
            'phone' => ValidationRules::phone(),
            'password' => ValidationRules::password(),
            'region' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'position' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingApplication::positionOptions()))],
            'company_or_private' => ['required', 'in:company,private'],
            'company_name' => ['required_if:company_or_private,company', 'nullable', 'string', 'max:255'],
            'company_address' => ['required_if:company_or_private,company', 'nullable', 'string', 'max:500'],
            'profile_photo' => ProfilePhotoStorage::rules(),
        ];

        if ($category === 'new_applicant') {
            $rules = array_merge($rules, NewApplicantRegistrationRules::educationRules());
        }

        if ($category === 'trained_person') {
            $rules['course_id'] = ['required', 'exists:courses,id'];
            $rules['trained_year'] = ['required', 'integer', 'min:2000', 'max:2100'];
            $rules['certificate_number'] = ['required', 'string', 'max:100'];
            $rules['training_certificate'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        return $rules;
    }

    private function attributeNames(): array
    {
        return array_merge(NewApplicantRegistrationRules::attributeNames(), [
            'registration_category' => __('registration category'),
            'password' => __('password'),
            'course_id' => __('course trained'),
            'trained_year' => __('year trained'),
            'certificate_number' => __('certificate number'),
            'training_certificate' => __('training certificate'),
            'profile_photo' => __('profile photo'),
        ]);
    }
}
