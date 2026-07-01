<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::withCount('trainingApplications')
            ->orderByDesc('session_year')
            ->orderBy('name')
            ->paginate(15);

        return view('courses.index', compact('courses'));
    }

    public function create(): View
    {
        return view('courses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedCourseData($request);

        Course::create([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
            'is_published' => false,
            'published_at' => null,
        ]);

        return redirect()->route('courses.index')->with('status', __('Course created successfully. Set dates and publish when ready.'));
    }

    public function edit(Course $course): View
    {
        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $this->validatedCourseData($request, $course);

        $course->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($course->is_published && ! $course->canBePublished()) {
            $course->update([
                'is_published' => false,
                'published_at' => null,
            ]);

            return redirect()->route('courses.edit', $course)->with('error', __('Course was unpublished because application dates or session year are incomplete or invalid.'));
        }

        return redirect()->route('courses.index')->with('status', __('Course updated successfully.'));
    }

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->trainingApplications()->exists()) {
            return redirect()->route('courses.index')->with('error', __('Cannot delete a course that has training applications. Unpublish it instead.'));
        }

        $course->delete();

        return redirect()->route('courses.index')->with('status', __('Course deleted successfully.'));
    }

    public function publish(Course $course): RedirectResponse
    {
        if (! $course->canBePublished()) {
            return redirect()->route('courses.edit', $course)->with('error', __('Set session year, application open date, and deadline before publishing.'));
        }

        $course->update([
            'is_published' => true,
            'published_at' => now(),
            'is_active' => true,
        ]);

        return redirect()->route('courses.index')->with('status', __('Course published. Trainees can apply within the application window.'));
    }

    public function unpublish(Course $course): RedirectResponse
    {
        $course->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return redirect()->route('courses.index')->with('status', __('Course unpublished. Trainees can no longer apply.'));
    }

    public function newSession(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'session_year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], ValidationRules::requiredMessages(), [
            'session_year' => __('session year'),
        ]);

        $sessionYear = (int) $request->session_year;

        if ($course->code) {
            $exists = Course::where('code', $course->code)
                ->where('session_year', $sessionYear)
                ->exists();

            if ($exists) {
                return redirect()->route('courses.edit', $course)->with('error', __('A course with this code already exists for session :year.', ['year' => $sessionYear]));
            }
        }

        $newCourse = $course->replicateForNewSession($sessionYear);

        return redirect()->route('courses.edit', $newCourse)->with('status', __('New session created for :year. Set application dates and publish when ready.', ['year' => $sessionYear]));
    }

    private function validatedCourseData(Request $request, ?Course $course = null): array
    {
        $sessionYear = (int) $request->input('session_year');

        $codeRules = ['nullable', 'string', 'max:50'];
        if ($request->filled('code')) {
            $codeRules[] = Rule::unique('courses', 'code')
                ->where(fn ($query) => $query->where('session_year', $sessionYear))
                ->ignore($course?->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => $codeRules,
            'session_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'application_opens_at' => ['nullable', 'date'],
            'application_deadline_at' => ['nullable', 'date', 'after_or_equal:application_opens_at'],
        ], ValidationRules::requiredMessages(), [
            'name' => __('course name'),
            'code' => __('course code'),
            'session_year' => __('session year'),
            'application_opens_at' => __('application open date'),
            'application_deadline_at' => __('application deadline'),
        ]);
    }
}
