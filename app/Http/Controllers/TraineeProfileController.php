<?php

namespace App\Http\Controllers;

use App\Models\EducationBackground;
use App\Support\PaginationHelper;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TraineeProfileController extends Controller
{
    /**
     * Show trainee profile form (education background). Trainee only.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        $educationBackgrounds = $user->educationBackgrounds()
            ->orderByDesc('created_at')
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        return view('trainee.profile', compact('user', 'educationBackgrounds'));
    }

    /**
     * Preview certificate document (inline in browser). Owner or admin/staff only.
     */
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
            'Content-Disposition' => 'inline; filename="' . basename($educationBackground->certificate_path) . '"',
        ]);
    }

    /**
     * Store education background with certificate (certified by advocate).
     */
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

        return redirect()->route('trainee.profile.edit')->with('status', __('Education background added. You may add more or go to the dashboard.'));
    }
}
