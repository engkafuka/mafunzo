<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            ->get();

        $pendingApplications = $request->user()
            ->trainingApplications()
            ->with('course')
            ->where('status', 'pending_payment')
            ->get()
            ->keyBy('course_id');

        return view('training.select-course', compact('courses', 'pendingApplications'));
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

        if ($this->hasPendingApplicationForCourse($request->user(), $course->id)) {
            return redirect()->route('training.my-applications')->with('error', __('You already have a pending application for this course. Complete payment first.'));
        }

        $user = $request->user();
        return view('training.application-form', [
            'course' => $course,
            'user' => $user,
        ]);
    }

    /**
     * Store application, generate control number, redirect to payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTrainee();
        $user = $request->user();
        $course = Course::findOrFail($request->course_id);

        if (! $course->isAcceptingApplications()) {
            return back()->with('error', __('Applications are not open for this course.'));
        }

        if ($this->hasPendingApplicationForCourse($user, $course->id)) {
            return redirect()->route('training.my-applications')->with('error', __('You already have a pending application for this course. Complete payment first.'));
        }

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'first_name' => ValidationRules::personName(),
            'middle_name' => ValidationRules::personName(false),
            'last_name' => ValidationRules::personName(),
            'email' => ['required', 'email', 'max:255'],
            'phone' => ValidationRules::phone(),
            'region' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'company_or_private' => ['required', 'in:company,private'],
            'company_name' => ['required_if:company_or_private,company', 'nullable', 'string', 'max:255'],
            'company_address' => ['required_if:company_or_private,company', 'nullable', 'string', 'max:500'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'position' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingApplication::positionOptions()))],
        ], ValidationRules::requiredMessages(), [
            'first_name' => __('first name'),
            'middle_name' => __('middle name'),
            'last_name' => __('last name'),
            'email' => __('email'),
            'phone' => __('phone number'),
            'region' => __('region'),
            'district' => __('district'),
            'company_or_private' => __('company / private'),
            'company_name' => __('company name'),
            'company_address' => __('company address'),
            'gender' => __('gender'),
            'date_of_birth' => __('date of birth'),
            'position' => __('position'),
        ]);

        $validated['user_id'] = $user->id;
        $validated['status'] = 'pending_payment';
        $validated['control_number'] = $this->generateControlNumber();

        if (($validated['company_or_private'] ?? '') === 'private') {
            $validated['company_name'] = null;
            $validated['company_address'] = null;
        }

        $application = TrainingApplication::create($validated);

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
        if ($application->status === 'payment_completed') {
            return redirect()->route('training.confirmation', $application);
        }
        return view('training.payment', compact('application'));
    }

    /**
     * Mark payment as completed (admin or trainee confirms), generate registration number.
     */
    public function confirmPayment(TrainingApplication $application): RedirectResponse
    {
        $this->authorizeTrainee();
        if ($application->user_id !== auth()->id()) {
            abort(403);
        }
        if ($application->status === 'payment_completed') {
            return redirect()->route('training.confirmation', $application);
        }

        $application->update([
            'status' => 'payment_completed',
            'payment_completed_at' => now(),
            'registration_number' => $this->generateRegistrationNumber(),
        ]);

        return redirect()->route('training.confirmation', $application);
    }

    /**
     * Show confirmation with registration number.
     */
    public function confirmation(TrainingApplication $application): View|RedirectResponse
    {
        $this->authorizeTrainee();
        if ($application->user_id !== auth()->id()) {
            abort(403);
        }
        if ($application->status !== 'payment_completed') {
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
        $applications = $request->user()->trainingApplications()->with('course')->latest()->get();
        return view('training.my-applications', compact('applications'));
    }

    private function hasPendingApplicationForCourse($user, int $courseId): bool
    {
        return $user->trainingApplications()
            ->where('course_id', $courseId)
            ->where('status', 'pending_payment')
            ->exists();
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

    private function generateControlNumber(): string
    {
        do {
            $number = strtoupper(Str::random(2)) . date('Ymd') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (TrainingApplication::where('control_number', $number)->exists());
        return $number;
    }

    private function generateRegistrationNumber(): string
    {
        $year = date('Y');
        $last = TrainingApplication::whereNotNull('registration_number')
            ->whereYear('payment_completed_at', $year)
            ->orderByDesc('id')
            ->first();
        $seq = $last ? (int) substr($last->registration_number, -4) + 1 : 1;
        return sprintf('WRRB/%s/1/%04d', $year, $seq);
    }
}
