<?php

namespace App\Support;

use App\Models\Course;
use App\Models\TrainingApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamResultsExporter
{
    public static function applicationsForCourse(int $courseId): Collection
    {
        return TrainingApplication::with('course')
            ->where('course_id', $courseId)
            ->where('status', 'payment_completed')
            ->whereNotNull('exam_uploaded_at')
            ->orderBy('registration_number')
            ->get();
    }

    public static function exportPdf(int $courseId): Response
    {
        $course = Course::findOrFail($courseId);
        $applications = self::applicationsForCourse($courseId);

        $filename = self::safeFilename($course->name).'-exam-results.pdf';

        return Pdf::loadView('exports.exam-results-pdf', [
            'course' => $course,
            'applications' => $applications,
            'generatedAt' => now(),
        ])->download($filename);
    }

    public static function exportExcel(int $courseId): StreamedResponse
    {
        $course = Course::findOrFail($courseId);
        $applications = self::applicationsForCourse($courseId);
        $filename = self::safeFilename($course->name).'-exam-results.csv';

        return response()->streamDownload(function () use ($course, $applications) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('Registration number'),
                __('First name'),
                __('Middle name'),
                __('Last name'),
                __('Email'),
                __('Phone'),
                __('Score'),
                __('Passed'),
                __('Recorded at'),
                __('Published at'),
            ]);

            foreach ($applications as $application) {
                fputcsv($handle, [
                    $application->registration_number,
                    $application->first_name,
                    $application->middle_name,
                    $application->last_name,
                    $application->email,
                    $application->phone,
                    $application->exam_score !== null ? number_format((float) $application->exam_score, 2) : '',
                    $application->exam_passed === true ? __('Yes') : ($application->exam_passed === false ? __('No') : ''),
                    $application->exam_uploaded_at?->format('Y-m-d H:i'),
                    $application->exam_results_published_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function safeFilename(string $name): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($name)) ?: 'course';

        return trim($slug, '-');
    }
}
