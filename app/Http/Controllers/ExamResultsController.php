<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use App\Notifications\TraineeStatusNotification;
use App\Support\AuditLogger;
use App\Support\ExamResultsExporter;
use App\Support\IdentityCardService;
use App\Support\PaginationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamResultsController extends Controller
{
    public function index(Request $request): View
    {
        $courseId = $request->query('course_id');
        $courses = Course::orderBy('name')->get();
        $applications = collect();
        $examStats = null;

        if ($courseId) {
            $applications = TrainingApplication::with('course')
                ->where('course_id', $courseId)
                ->where('status', 'payment_completed')
                ->orderBy('registration_number')
                ->paginate(PaginationHelper::PER_PAGE)
                ->withQueryString();

            $baseQuery = TrainingApplication::query()
                ->where('course_id', $courseId)
                ->where('status', 'payment_completed');

            $examStats = [
                'recorded' => (clone $baseQuery)->whereNotNull('exam_uploaded_at')->count(),
                'awaiting_publish' => (clone $baseQuery)
                    ->whereNotNull('exam_uploaded_at')
                    ->whereNull('exam_results_published_at')
                    ->count(),
                'published' => (clone $baseQuery)->whereNotNull('exam_results_published_at')->count(),
            ];
        }

        $isTrainerPortal = $request->routeIs('trainer.*');
        $canPublish = auth()->user()?->isAdminOrSuperAdmin() ?? false;

        return view('application-management.exam-results', compact(
            'applications',
            'courses',
            'courseId',
            'isTrainerPortal',
            'canPublish',
            'examStats',
        ));
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
        $savedCount = 0;

        foreach ($request->results as $row) {
            $application = TrainingApplication::find($row['id'] ?? 0);

            if (! $application) {
                continue;
            }

            $courseId = $application->course_id;

            $score = isset($row['exam_score']) && $row['exam_score'] !== '' ? (float) $row['exam_score'] : null;
            $passed = isset($row['exam_passed']) && $row['exam_passed'] !== '' ? (bool) (int) $row['exam_passed'] : null;

            $scoreChanged = $application->exam_score != $score;
            $passedChanged = $application->exam_passed !== $passed;

            $application->update([
                'exam_score' => $score,
                'exam_passed' => $passed,
                'exam_uploaded_at' => now(),
                'exam_results_published_at' => ($scoreChanged || $passedChanged)
                    ? null
                    : $application->exam_results_published_at,
            ]);

            $savedCount++;
        }

        $redirectRoute = $request->routeIs('trainer.*') ? 'trainer.exam-results' : 'app-management.exam-results';

        $message = auth()->user()?->isAdminOrSuperAdmin()
            ? __('Exam results saved. Publish when ready for trainees to view them.')
            : __('Exam results saved. An administrator must publish them before trainees can view the results.');

        if ($savedCount === 0) {
            $message = __('No exam results were saved.');
        }

        return redirect()->route($redirectRoute, ['course_id' => $courseId])
            ->with('status', $message);
    }

    public function publish(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        $course = Course::findOrFail($request->integer('course_id'));

        $applications = TrainingApplication::query()
            ->with('user')
            ->where('course_id', $course->id)
            ->where('status', 'payment_completed')
            ->whereNotNull('exam_uploaded_at')
            ->whereNull('exam_results_published_at')
            ->get();

        if ($applications->isEmpty()) {
            return redirect()
                ->route('app-management.exam-results', ['course_id' => $course->id])
                ->with('error', __('No saved examination results are waiting to be published for this course.'));
        }

        $publishedAt = now();
        TrainingApplication::query()
            ->whereIn('id', $applications->pluck('id'))
            ->update(['exam_results_published_at' => $publishedAt]);

        $drafted = 0;
        foreach ($applications as $application) {
            $application->exam_results_published_at = $publishedAt;
            $application->loadMissing(['user', 'course', 'warehouseIdentityCard']);

            if ($application->user) {
                $application->user->notify(new TraineeStatusNotification(
                    __('Examination results published'),
                    __('Your examination results for :course are now available.', [
                        'course' => $course->name,
                    ]),
                    route('training.exam-results'),
                    __('View results'),
                ));
            }

            if ($application->isEligibleForIdentityCard() && ! $application->warehouseIdentityCard) {
                try {
                    IdentityCardService::generate($application, auth()->user());
                    $drafted++;
                } catch (\RuntimeException) {
                    // Keep publishing successful even if draft generation fails for one trainee.
                }
            }
        }

        AuditLogger::logAction(
            __('Published examination results for :course', ['course' => $course->name]),
            $course,
            null,
            ['published_count' => $applications->count(), 'id_drafts_created' => $drafted],
        );

        $message = __(':count examination result(s) published. Trainees can now view their results.', [
            'count' => $applications->count(),
        ]);
        if ($drafted > 0) {
            $message .= ' '.__(':count identity card draft(s) were created for eligible trainees.', ['count' => $drafted]);
        }

        return redirect()
            ->route('app-management.exam-results', ['course_id' => $course->id])
            ->with('status', $message);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorizeAdmin();

        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        return ExamResultsExporter::exportPdf($request->integer('course_id'));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        return ExamResultsExporter::exportExcel($request->integer('course_id'));
    }

    private function authorizeAdmin(): void
    {
        if (! auth()->user()?->isAdminOrSuperAdmin()) {
            abort(403);
        }
    }
}
