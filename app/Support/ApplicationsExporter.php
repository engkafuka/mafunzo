<?php

namespace App\Support;

use App\Models\Course;
use App\Models\TrainingApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationsExporter
{
    public static function filteredQuery(Request $request): Builder
    {
        $query = TrainingApplication::with(['course', 'user'])
            ->where('status', '!=', 'pending_registration')
            ->whereNotNull('course_id')
            ->where('application_type', '!=', 'legacy_expert')
            ->orderByDesc('created_at');

        if ($request->filled('status_filter')) {
            if ($request->status_filter === 'pending_review') {
                $query->where('application_review_status', 'pending')->where('status', 'payment_completed');
            } elseif ($request->status_filter === 'pending_payment') {
                $query->whereNull('payment_verified_at')
                    ->whereIn('status', ['pending_payment', 'payment_completed']);
            }
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('q')) {
            $term = '%'.addcslashes(trim($request->string('q')->toString()), '%_\\').'%';
            $query->where(function ($qry) use ($term) {
                $qry->where('registration_number', 'like', $term)
                    ->orWhere('control_number', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('middle_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhereHas('course', fn ($course) => $course->where('name', 'like', $term));
            });
        }

        return $query;
    }

    /**
     * @return array<string, string>
     */
    public static function activeFilterLabels(Request $request): array
    {
        $filters = [];

        if ($request->filled('q')) {
            $filters[__('Search')] = $request->string('q')->toString();
        }

        if ($request->filled('course_id')) {
            $course = Course::find($request->integer('course_id'));
            $filters[__('Course')] = $course?->name ?? '#'.$request->integer('course_id');
        }

        if ($request->filled('status_filter')) {
            $filters[__('Status filter')] = match ($request->string('status_filter')->toString()) {
                'pending_review' => __('Pending review'),
                'pending_payment' => __('Pending payment verify'),
                default => $request->string('status_filter')->toString(),
            };
        }

        return $filters;
    }

    public static function exportPdf(Request $request): Response
    {
        $applications = self::filteredQuery($request)->get();

        return Pdf::loadView('exports.applications-pdf', [
            'applications' => $applications,
            'filters' => self::activeFilterLabels($request),
            'generatedAt' => now(),
        ])->download(self::filename($request, 'pdf'));
    }

    public static function exportExcel(Request $request): StreamedResponse
    {
        $applications = self::filteredQuery($request)->get();
        $filename = self::filename($request, 'csv');

        return response()->streamDownload(function () use ($applications) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('Registration'),
                __('Control number'),
                __('First name'),
                __('Middle name'),
                __('Last name'),
                __('Email'),
                __('Phone'),
                __('Course'),
                __('Status'),
                __('Review status'),
                __('Payment verified'),
                __('Company'),
                __('Region'),
                __('District'),
            ]);

            foreach ($applications as $application) {
                fputcsv($handle, [
                    $application->registration_number,
                    $application->control_number,
                    $application->first_name,
                    $application->middle_name,
                    $application->last_name,
                    $application->email,
                    $application->phone,
                    $application->course?->name,
                    $application->status,
                    $application->application_review_status,
                    $application->payment_verified_at ? __('Yes') : __('No'),
                    $application->company_name,
                    $application->region,
                    $application->district,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function filename(Request $request, string $extension): string
    {
        $parts = ['applications'];

        if ($request->filled('course_id')) {
            $course = Course::find($request->integer('course_id'));
            if ($course?->name) {
                $parts[] = Str::slug($course->name);
            }
        }

        $parts[] = now()->format('Ymd-His');

        return implode('-', $parts).'.'.$extension;
    }
}
