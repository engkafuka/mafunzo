<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Notifications\TraineeStatusNotification;
use App\Support\AuditLogger;
use App\Support\CourseMaterialAccess;
use App\Support\CourseMaterialStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $courseId = $request->query('course_id');
        $courses = Course::orderByDesc('session_year')->orderBy('name')->get();
        $selectedCourse = $courseId ? Course::find($courseId) : null;
        $materials = collect();
        $traineeCount = null;

        if ($selectedCourse) {
            $materials = $selectedCourse->materials()
                ->with(['uploader', 'publisher'])
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();
            $traineeCount = CourseMaterialAccess::eligibleApplicationsQuery($selectedCourse->id)->count();
        }

        return view('application-management.materials', [
            'courses' => $courses,
            'courseId' => $courseId,
            'selectedCourse' => $selectedCourse,
            'materials' => $materials,
            'traineeCount' => $traineeCount,
            'categoryOptions' => CourseMaterial::categoryOptions(),
            'canPublish' => auth()->user()?->isAdminOrSuperAdmin() ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(CourseMaterial::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:20480'],
        ]);

        $course = Course::findOrFail($request->integer('course_id'));
        $fileMeta = CourseMaterialStorage::store($course, $request->file('file'));
        $nextSort = (int) $course->materials()->max('sort_order') + 1;

        $material = $course->materials()->create([
            'title' => $request->string('title')->toString(),
            'category' => $request->string('category')->toString(),
            'description' => $request->filled('description') ? $request->string('description')->toString() : null,
            ...$fileMeta,
            'sort_order' => $nextSort,
            'uploaded_by' => auth()->id(),
        ]);

        AuditLogger::logAction(
            __('Uploaded training material for :course', ['course' => $course->name]),
            $material,
            null,
            ['title' => $material->title, 'category' => $material->category],
        );

        $message = auth()->user()?->isAdminOrSuperAdmin()
            ? __('Material uploaded. Publish it when trainees should be able to access it.')
            : __('Material uploaded. An administrator must publish it before trainees can access it.');

        return redirect()
            ->route('app-management.materials', ['course_id' => $course->id])
            ->with('status', $message);
    }

    public function publish(CourseMaterial $material): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($material->isPublished()) {
            return redirect()
                ->route('app-management.materials', ['course_id' => $material->course_id])
                ->with('error', __('This material is already published.'));
        }

        $material->load('course');
        $applications = CourseMaterialAccess::eligibleApplicationsQuery($material->course_id)->get();

        $material->update([
            'published_at' => now(),
            'published_by' => auth()->id(),
        ]);

        $notificationFailures = 0;

        foreach ($applications as $application) {
            if (! $application->user) {
                continue;
            }

            try {
                $application->user->notify(new TraineeStatusNotification(
                    __('New training material available'),
                    __(':title is now available for :course.', [
                        'title' => $material->title,
                        'course' => $material->course->name,
                    ]),
                    route('training.materials'),
                    __('View materials'),
                ));
            } catch (\Throwable $e) {
                $notificationFailures++;
                Log::warning('Course material publish notification failed.', [
                    'material_id' => $material->id,
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        AuditLogger::logAction(
            __('Published training material :title for :course', [
                'title' => $material->title,
                'course' => $material->course->name,
            ]),
            $material,
            null,
            ['notified_count' => $applications->count()],
        );

        $message = __('Material published to :count trainee account(s).', [
            'count' => $applications->count(),
        ]);

        if ($notificationFailures > 0) {
            $message .= ' '.__('Some trainee notifications could not be sent. The material is still published.');
        }

        return redirect()
            ->route('app-management.materials', ['course_id' => $material->course_id])
            ->with('status', $message);
    }

    public function unpublish(CourseMaterial $material): RedirectResponse
    {
        $this->authorizeAdmin();

        $material->update([
            'published_at' => null,
            'published_by' => null,
        ]);

        AuditLogger::logAction(
            __('Unpublished training material :title', ['title' => $material->title]),
            $material,
        );

        return redirect()
            ->route('app-management.materials', ['course_id' => $material->course_id])
            ->with('status', __('Material unpublished. Trainees can no longer access it.'));
    }

    public function replace(Request $request, CourseMaterial $material): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx', 'max:20480'],
        ]);

        $wasPublished = $material->isPublished();
        $fileMeta = CourseMaterialStorage::replace($material, $request->file('file'));

        $material->update([
            ...$fileMeta,
            'uploaded_by' => auth()->id(),
            'published_at' => null,
            'published_by' => null,
        ]);

        AuditLogger::logAction(
            __('Replaced file for training material :title', ['title' => $material->title]),
            $material,
            null,
            ['filename' => $material->original_filename],
        );

        $message = $wasPublished
            ? __('Material file replaced. Publish again before trainees can access the new file.')
            : __('Material file replaced.');

        return redirect()
            ->route('app-management.materials', ['course_id' => $material->course_id])
            ->with('status', $message);
    }

    public function destroy(CourseMaterial $material): RedirectResponse
    {
        if ($material->isPublished() && ! auth()->user()?->isAdminOrSuperAdmin()) {
            abort(403);
        }

        $courseId = $material->course_id;
        $title = $material->title;

        CourseMaterialStorage::delete($material->file_path);
        $material->delete();

        AuditLogger::logAction(
            __('Deleted training material :title', ['title' => $title]),
            null,
            null,
            ['course_id' => $courseId, 'title' => $title],
        );

        return redirect()
            ->route('app-management.materials', ['course_id' => $courseId])
            ->with('status', __('Material deleted.'));
    }

    public function download(CourseMaterial $material): BinaryFileResponse
    {
        $path = CourseMaterialStorage::absolutePath($material->file_path);
        if (! $path) {
            abort(404);
        }

        return response()->download(
            $path,
            $material->original_filename,
            ['Content-Type' => $material->mime_type ?? 'application/octet-stream'],
        );
    }

    private function authorizeAdmin(): void
    {
        if (! auth()->user()?->isAdminOrSuperAdmin()) {
            abort(403);
        }
    }
}
