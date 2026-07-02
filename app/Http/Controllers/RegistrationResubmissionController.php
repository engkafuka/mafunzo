<?php



namespace App\Http\Controllers;



use App\Models\Course;

use App\Models\EducationBackground;

use App\Models\TrainingApplication;

use App\Models\User;

use App\Support\NewApplicantRegistrationRules;

use App\Support\TrainedPersonRegistrationRules;

use App\Support\ValidationRules;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

use Illuminate\View\View;



class RegistrationResubmissionController extends Controller

{

    public function edit(Request $request): View|RedirectResponse

    {

        $user = $request->user();



        if (! $user || $user->role !== 'trainee' || ! $user->canResubmitRegistration()) {

            return redirect()->route('registration.pending');

        }



        $user->load(['educationBackgrounds', 'trainingApplications.course']);



        $legacyApplication = $user->isTrainedPerson()

            ? $user->trainingApplications()

                ->where('application_type', 'legacy_expert')

                ->latest()

                ->first()

            : null;



        $courses = Course::orderByDesc('session_year')->orderBy('name')->get();



        return view('auth.registration-resubmit', compact('user', 'legacyApplication', 'courses'));

    }



    public function update(Request $request): RedirectResponse

    {

        $user = $request->user();



        if (! $user || $user->role !== 'trainee' || ! $user->canResubmitRegistration()) {

            return redirect()->route('registration.pending');

        }



        if ($user->isNewApplicant()) {

            return $this->updateNewApplicant($request, $user);

        }



        return $this->updateTrainedPerson($request, $user);

    }



    private function updateNewApplicant(Request $request, User $user): RedirectResponse

    {

        $rules = array_merge(

            NewApplicantRegistrationRules::personalRules($user->id),

            NewApplicantRegistrationRules::educationRules(certificatesRequired: false),

        );



        $validated = $request->validate(

            $rules,

            ValidationRules::registrationMessages(),

            NewApplicantRegistrationRules::attributeNames(),

        );



        NewApplicantRegistrationRules::validateEducationRows($request, certificatesRequired: false);



        DB::transaction(function () use ($request, $validated, $user) {

            $this->updateUserPersonalDetails($user, $validated);

            $user->update(['profile_completed_at' => now()]);

            $this->syncEducationBackgrounds($user, $request, $validated['education']);

        });



        return redirect()->route('registration.pending')

            ->with('status', __('Your registration has been updated and resubmitted for staff review.'));

    }



    private function updateTrainedPerson(Request $request, User $user): RedirectResponse

    {

        $legacyApplication = $user->trainingApplications()

            ->where('application_type', 'legacy_expert')

            ->latest()

            ->first();



        if (! $legacyApplication) {

            return redirect()->route('registration.pending')

                ->with('error', __('Your previous training record could not be found. Please contact WRRB staff.'));

        }



        $rules = array_merge(

            NewApplicantRegistrationRules::personalRules($user->id),

            TrainedPersonRegistrationRules::trainingRules(certificatesRequired: false),

        );



        $validated = $request->validate(

            $rules,

            ValidationRules::registrationMessages(),

            array_merge(NewApplicantRegistrationRules::attributeNames(), TrainedPersonRegistrationRules::attributeNames()),

        );



        TrainedPersonRegistrationRules::validateTrainingCertificate(

            $request,

            (bool) $legacyApplication->certificate_path,

        );



        DB::transaction(function () use ($request, $validated, $user, $legacyApplication) {

            $this->updateUserPersonalDetails($user, $validated);



            $certificatePath = $legacyApplication->certificate_path;



            if ($request->hasFile('training_certificate')) {

                if ($legacyApplication->certificate_path) {

                    Storage::disk('local')->delete($legacyApplication->certificate_path);

                }



                $certificatePath = $request->file('training_certificate')->store('certificates/legacy', 'local');

            }



            $legacyApplication->update([

                'course_id' => $validated['course_id'],

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

                'certificate_path' => $certificatePath,

                'status' => 'pending_registration',

                'application_review_status' => 'pending',

            ]);

        });



        return redirect()->route('registration.pending')

            ->with('status', __('Your registration has been updated and resubmitted for staff review.'));

    }



    private function updateUserPersonalDetails(User $user, array $validated): void

    {

        $name = trim($validated['first_name'].' '.$validated['middle_name'].' '.$validated['last_name']);



        $user->update([

            'name' => $name,

            'first_name' => $validated['first_name'],

            'middle_name' => $validated['middle_name'],

            'last_name' => $validated['last_name'],

            'email' => $validated['email'],

            'phone' => $validated['phone'],

            'region' => $validated['region'],

            'district' => $validated['district'],

            'gender' => $validated['gender'],

            'date_of_birth' => $validated['date_of_birth'],

            'position' => $validated['position'],

            'company_or_private' => $validated['company_or_private'],

            'company_name' => $validated['company_or_private'] === 'company' ? $validated['company_name'] : null,

            'company_address' => $validated['company_or_private'] === 'company' ? $validated['company_address'] : null,

            'registration_status' => 'pending',

            'registration_reviewed_at' => null,

            'registration_reviewed_by' => null,

            'registration_rejection_reason' => null,

        ]);

    }



    private function syncEducationBackgrounds(User $user, Request $request, array $educationRows): void

    {

        $keptIds = [];



        foreach ($educationRows as $index => $education) {

            $certificatePath = null;

            $existing = null;



            if (! empty($education['id'])) {

                $existing = EducationBackground::query()

                    ->where('user_id', $user->id)

                    ->whereKey($education['id'])

                    ->first();



                if (! $existing) {

                    continue;

                }



                $certificatePath = $existing->certificate_path;

            }



            if ($request->hasFile("education.$index.certificate")) {

                if ($existing?->certificate_path) {

                    Storage::disk('local')->delete($existing->certificate_path);

                }



                $certificatePath = $request->file("education.$index.certificate")->store('certificates', 'local');

            }



            $payload = [

                'level' => $education['level'],

                'program' => $education['program'],

                'program_other' => $education['program'] === 'others' ? ($education['program_other'] ?? null) : null,

                'institution' => $education['institution'],

                'certificate_path' => $certificatePath,

            ];



            if ($existing) {

                $existing->update($payload);

                $keptIds[] = $existing->id;

            } else {

                $created = EducationBackground::create(array_merge($payload, ['user_id' => $user->id]));

                $keptIds[] = $created->id;

            }

        }



        $user->educationBackgrounds()

            ->whereNotIn('id', $keptIds)

            ->get()

            ->each(function (EducationBackground $educationBackground) {

                if ($educationBackground->certificate_path) {

                    Storage::disk('local')->delete($educationBackground->certificate_path);

                }

                $educationBackground->delete();

            });

    }

}


