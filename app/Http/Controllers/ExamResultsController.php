<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use App\Support\PaginationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamResultsController extends Controller
{
    public function index(Request $request): View
    {
        $courseId = $request->query('course_id');
        $courses = Course::orderBy('name')->get();
        $applications = collect();

        if ($courseId) {
            $applications = TrainingApplication::with('course')
                ->where('course_id', $courseId)
                ->where('status', 'payment_completed')
                ->orderBy('registration_number')
                ->paginate(PaginationHelper::PER_PAGE)
                ->withQueryString();
        }

        $isTrainerPortal = $request->routeIs('trainer.*');

        return view('application-management.exam-results', compact('applications', 'courses', 'courseId', 'isTrainerPortal'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'results' => 'required|array',
            'results.*.id' => 'required|exists:training_applications,id',
            'results.*.exam_score' => 'nullable|numeric|min:0|max:100',
            'results.*.exam_passed' => 'nullable|boolean',
        ]);

        $courseId = null;

        foreach ($request->results as $row) {
            $application = TrainingApplication::find($row['id'] ?? 0);

            if (! $application) {
                continue;
            }

            $courseId = $application->course_id;

            $application->update([
                'exam_score' => isset($row['exam_score']) && $row['exam_score'] !== '' ? (float) $row['exam_score'] : null,
                'exam_passed' => isset($row['exam_passed']) && $row['exam_passed'] !== '' ? (bool) (int) $row['exam_passed'] : null,
                'exam_uploaded_at' => now(),
            ]);
        }

        $redirectRoute = $request->routeIs('trainer.*') ? 'trainer.exam-results' : 'app-management.exam-results';

        return redirect()->route($redirectRoute, ['course_id' => $courseId])
            ->with('status', __('Exam results saved.'));
    }
}
