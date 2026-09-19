<?php

namespace App\Http\Controllers;

use App\Models\EducationBackground;
use App\Support\NewApplicantRegistrationRules;
use App\Support\PaginationHelper;
use App\Support\ProfilePhotoStorage;
use App\Support\TraineeProfileUpdateRequestHandler;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TraineeProfileController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        $user->load('educationBackgrounds');

        $legacyApplication = $user->isTrainedPerson()
            ? $user->trainingApplications()
                ->with('course')
                ->where('application_type', 'legacy_expert')
                ->latest()
                ->first()
            : null;

        $educationBackgrounds = $user->educationBackgrounds()
            ->orderByDesc('created_at')
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        return view('trainee.profile', compact('user', 'educationBackgrounds', 'legacyApplication'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        try {
            TraineeProfileUpdateRequestHandler::handle($request, $user);
        } catch (\RuntimeException $exception) {
            return redirect()->route('trainee.profile.edit')->with('error', $exception->getMessage());
        }

        return redirect()->route('trainee.profile.edit')->with('status', __('Profile updated successfully.'));
    }

    public function showCertificate(Request $request, EducationBackground $educationBackground): Response|RedirectResponse
    {
        $user = $request->user();
        $canView = $educationBackground->user_id === $user->id
            || in_array($user->role, ['super_admin', 'admin', 'staff'], true);

        if (! $canView || ! $educationBackground->certificate_path) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($educationBackground->certificate_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($educationBackground->certificate_path);
        $ext = strtolower(pathinfo($educationBackground->certificate_path, PATHINFO_EXTENSION));
        $mimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($educationBackground->certificate_path).'"',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'level' => ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::levelOptions()))],
            'program' => ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::programOptions()))],
            'program_other' => ['required_if:program,others', 'nullable', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], ValidationRules::requiredMessages(), [
            'level' => __('level'),
            'program' => __('program'),
            'program_other' => __('program specification'),
            'institution' => __('institution'),
            'certificate' => __('certificate'),
        ]);

        $path = $request->file('certificate')->store('certificates', 'local');

        EducationBackground::create([
            'user_id' => $user->id,
            'level' => $request->level,
            'program' => $request->program,
            'program_other' => $request->program === 'others' ? $request->program_other : null,
            'institution' => $request->institution,
            'certificate_path' => $path,
        ]);

        $user->update(['profile_completed_at' => now()]);

        return redirect()->route('trainee.profile.edit')->with('status', __('Education background added.'));
    }

}
