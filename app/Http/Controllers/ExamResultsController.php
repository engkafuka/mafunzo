<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use App\Notifications\TraineeStatusNotification;
use App\Support\AuditLogger;
use App\Support\ExamResultsExporter;
use App\Support\IdentityCardService;
use App\Support\PaginationHelper;
use App\Support\PositionExamAssigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
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
            $applications = TrainingApplication::with(['course', 'user.educationBackgrounds'])
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
        ]);

        $courseId = null;
        $savedCount = 0;
        $reassignedCount = 0;

        foreach ($request->results as $row) {
            $application = TrainingApplication::with('user.educationBackgrounds')
                ->find($row['id'] ?? 0);

            if (! $application) {
                continue;
            }

            $courseId = $application->course_id;

            $score = isset($row['exam_score']) && $row['exam_score'] !== '' ? (float) $row['exam_score'] : null;
            $assignment = PositionExamAssigner::resolve($application, $score);

            $scoreChanged = $application->exam_score != $score;
            $passedChanged = $application->exam_passed !== $assignment['exam_passed'];
            $positionChanged = $application->assigned_position !== $assignment['assigned_position'];

            if (
                $assignment['assigned_position']
                && $assignment['assigned_position'] !== $application->position
            ) {
                $reassignedCount++;
            }

            $application->update([
                'exam_score' => $score,
                'exam_passed' => $assignment['exam_passed'],
                'assigned_position' => $assignment['assigned_position'],
                'exam_uploaded_at' => $score === null ? $application->exam_uploaded_at : now(),
                'exam_results_published_at' => ($scoreChanged || $passedChanged || $positionChanged)
                    ? null
                    : $application->exam_results_published_at,
            ]);

            $savedCount++;
        }

        $redirectRoute = $request->routeIs('trainer.*') ? 'trainer.exam-results' : 'app-management.exam-results';

        $message = auth()->user()?->isAdminOrSuperAdmin()
            ? __('Exam results saved. Pass is automatic for scores ≥ :pass. Publish when ready for trainees to view them.', [
                'pass' => (int) config('position_exam_rules.pass_score', 50),
            ])
            : __('Exam results saved. An administrator must publish them before trainees can view the results.');

        if ($reassignedCount > 0) {
            $message .= ' '.__(':count applicant(s) did not meet gated position rules and were assigned to :fallback.', [
                'count' => $reassignedCount,
                'fallback' => PositionExamAssigner::fallbackGroupLabel(),
            ]);
        }

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
        $actor = auth()->user();

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
        $notificationFailures = 0;
        $identityCardFailures = 0;

        foreach ($applications as $application) {
            $application->exam_results_published_at = $publishedAt;
            $application->loadMissing(['user', 'course', 'warehouseIdentityCard']);

            if ($application->user) {
                try {
                    $application->user->notify(new TraineeStatusNotification(
                        __('Examination results published'),
                        __('Your examination results for :course are now available.', [
                            'course' => $course->name,
                        ]),
                        route('training.exam-results'),
                        __('View results'),
                    ));
                } catch (\Throwable $e) {
                    $notificationFailures++;
                    Log::warning('Exam results publish notification failed.', [
                        'application_id' => $application->id,
                        'user_id' => $application->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($application->isEligibleForIdentityCard() && ! $application->warehouseIdentityCard && $actor) {
                try {
                    IdentityCardService::generate($application, $actor);
                    $drafted++;
                } catch (\Throwable $e) {
                    $identityCardFailures++;
                    Log::warning('Exam results publish identity card draft failed.', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        try {
            AuditLogger::logAction(
                __('Published examination results for :course', ['course' => $course->name]),
                $course,
                null,
                ['published_count' => $applications->count(), 'id_drafts_created' => $drafted],
            );
        } catch (\Throwable $e) {
            Log::warning('Exam results publish audit log failed.', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
        }

        $message = __(':count examination result(s) published. Trainees can now view their results.', [
            'count' => $applications->count(),
        ]);
        if ($drafted > 0) {
            $message .= ' '.__(':count identity card draft(s) were created for eligible trainees.', ['count' => $drafted]);
        }
        if ($notificationFailures > 0 || $identityCardFailures > 0) {
            $message .= ' '.__('Some trainee notifications or identity card drafts could not be created. Results are still published.');
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
