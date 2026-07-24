<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use App\Support\PaginationHelper;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingApplicationController extends Controller
{
    /**
     * Select course (trainee only).
     */
    public function selectCourse(Request $request): View
    {
        $this->authorizeTrainee();
        $courses = Course::publishedForTrainees()
            ->orderByDesc('session_year')
            ->orderBy('name')
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        $openApplication = $request->user()
            ->trainingApplications()
            ->with('course')
            ->open()
            ->latest()
            ->first();

        $pendingApplications = $request->user()
            ->trainingApplications()
            ->with('course')
            ->where('status', 'pending_payment')
            ->get()
            ->keyBy('course_id');

        return view('training.select-course', compact('courses', 'pendingApplications', 'openApplication'));
    }

    /**
     * Show application form for selected course.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorizeTrainee();
        $course = Course::findOrFail($request->query('course_id'));

        if (! $course->isAcceptingApplications()) {
            return redirect()->route('training.select-course')->with('error', __('Applications are not open for this course.'));
        }

        if ($blocked = $this->openApplicationBlock($request->user(), (int) $course->id)) {
            return $blocked;
        }

        $user = $request->user()->load('educationBackgrounds');

        $missing = $user->missingFieldsForCourseApplication();
        if ($missing !== []) {
            return redirect()->route('trainee.profile.edit')
                ->with('error', __('Complete your profile before applying. Missing: :items.', [
                    'items' => implode(', ', $missing),
                ]));
        }

        return view('training.application-form', [
            'course' => $course,
            'user' => $user,
        ]);
    }

    /**
     * Store application from registration profile snapshot, redirect to payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTrainee();
        $user = $request->user();
        $course = Course::findOrFail($request->course_id);

        if (! $course->isAcceptingApplications()) {
            return back()->with('error', __('Applications are not open for this course.'));
        }

        if ($blocked = $this->openApplicationBlock($user, (int) $course->id)) {
            return $blocked;
        }

        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'confirm_details' => ['accepted'],
        ], ValidationRules::requiredMessages(), [
            'course_id' => __('course'),
            'confirm_details' => __('confirmation'),
        ]);

        $missing = $user->missingFieldsForCourseApplication();
        if ($missing !== []) {
            return redirect()->route('trainee.profile.edit')
                ->with('error', __('Complete your profile before applying. Missing: :items.', [
                    'items' => implode(', ', $missing),
                ]));
        }

        $application = TrainingApplication::create([
            ...$user->applicationSnapshotAttributes(),
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'pending_payment',
            'control_number' => null,
        ]);

        return redirect()->route('training.payment', $application);
    }

    /**
     * Payment page: show control number.
     */
    public function payment(TrainingApplication $application): View|RedirectResponse
    {
        $this->authorizeTrainee();
        if ($application->user_id !== auth()->id()) {
            abort(403);
        }
        if ($application->status === 'payment_completed' || $application->payment_verified_at) {
            return redirect()->route('training.confirmation', $application);
        }
        return view('training.payment', compact('application'));
    }

    /**
     * Show confirmation with registration number (after staff payment verification).
     */
    public function confirmation(TrainingApplication $application): View|RedirectResponse
    {
        $this->authorizeTrainee();
        if ($application->user_id !== auth()->id()) {
            abort(403);
        }
        if (! $application->payment_verified_at && $application->status !== 'payment_completed') {
            return redirect()->route('training.payment', $application);
        }
        return view('training.confirmation', compact('application'));
    }

    /**
     * My applications list (trainee).
     */
    public function index(Request $request): View
    {
        $this->authorizeTrainee();
        $applications = $request->user()->trainingApplications()
            ->with(['course', 'warehouseIdentityCard', 'user'])
            ->latest()
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();
        return view('training.my-applications', compact('applications'));
    }

    public function examResults(Request $request): View
    {
        $this->authorizeTrainee();

        $publishedResults = $request->user()
            ->trainingApplications()
            ->with('course')
            ->whereNotNull('course_id')
            ->where('status', 'payment_completed')
            ->whereNotNull('exam_results_published_at')
            ->latest()
            ->paginate(PaginationHelper::PER_PAGE, ['*'], 'published_page')
            ->withQueryString();

        $awaitingResults = $request->user()
            ->trainingApplications()
            ->with('course')
            ->whereNotNull('course_id')
            ->where('status', 'payment_completed')
            ->whereNull('exam_results_published_at')
            ->latest()
            ->paginate(PaginationHelper::PER_PAGE, ['*'], 'awaiting_page')
            ->withQueryString();

        return view('training.exam-results', compact('publishedResults', 'awaitingResults'));
    }

    /**
     * Block a new application when the trainee already has one open training (any course).
     */
    private function openApplicationBlock($user, int $courseId): ?RedirectResponse
    {
        $openApplication = $user->trainingApplications()
            ->with('course')
            ->open()
            ->latest()
            ->first();

        if (! $openApplication) {
            return null;
        }

        if ((int) $openApplication->course_id === $courseId && $openApplication->status === 'pending_payment') {
            return redirect()
                ->route('training.payment', $openApplication)
                ->with('error', __('You already have a pending application for this course. Complete payment first.'));
        }

        $courseName = $openApplication->course?->name ?? __('another course');

        return redirect()
            ->route('training.my-applications')
            ->with('error', __('You already have an open application for :course. Finish or wait until it is rejected before applying for another training.', [
                'course' => $courseName,
            ]));
    }

    private function authorizeTrainee(): void
    {
        if (auth()->user()->role !== 'trainee') {
            if (request()->expectsJson()) {
                abort(403, 'Only trainees can apply for training.');
            }
            redirect()->route('dashboard')->with('message', __('Only trainees can apply for training.'))->throwResponse();
        }
    }
}
