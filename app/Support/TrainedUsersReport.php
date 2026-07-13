<?php

namespace App\Support;

use App\Models\TrainingApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainedUsersReport
{
    /**
     * @return array<string, array{label: string, group: string, default: bool}>
     */
    public static function availableColumns(): array
    {
        return [
            'registration_number' => ['label' => __('Registration number'), 'group' => 'training', 'default' => true],
            'full_name' => ['label' => __('Full name'), 'group' => 'personal', 'default' => true],
            'first_name' => ['label' => __('First name'), 'group' => 'personal', 'default' => false],
            'middle_name' => ['label' => __('Middle name'), 'group' => 'personal', 'default' => false],
            'last_name' => ['label' => __('Last name'), 'group' => 'personal', 'default' => false],
            'gender' => ['label' => __('Gender'), 'group' => 'personal', 'default' => true],
            'date_of_birth' => ['label' => __('Date of birth'), 'group' => 'personal', 'default' => false],
            'email' => ['label' => __('Email'), 'group' => 'personal', 'default' => true],
            'phone' => ['label' => __('Phone'), 'group' => 'personal', 'default' => true],
            'region' => ['label' => __('Region'), 'group' => 'personal', 'default' => true],
            'district' => ['label' => __('District'), 'group' => 'personal', 'default' => false],
            'position' => ['label' => __('Position'), 'group' => 'employment', 'default' => true],
            'company_or_private' => ['label' => __('Company / Private'), 'group' => 'employment', 'default' => false],
            'company_name' => ['label' => __('Company name'), 'group' => 'employment', 'default' => true],
            'company_address' => ['label' => __('Company address'), 'group' => 'employment', 'default' => false],
            'course_name' => ['label' => __('Course'), 'group' => 'training', 'default' => true],
            'session_year' => ['label' => __('Session year'), 'group' => 'training', 'default' => true],
            'trained_year' => ['label' => __('Trained year'), 'group' => 'training', 'default' => false],
            'application_type' => ['label' => __('Applicant type'), 'group' => 'training', 'default' => false],
            'exam_score' => ['label' => __('Exam score'), 'group' => 'training', 'default' => true],
            'exam_passed' => ['label' => __('Exam passed'), 'group' => 'training', 'default' => true],
            'exam_results_published_at' => ['label' => __('Results published'), 'group' => 'training', 'default' => false],
            'certificate_issued_at' => ['label' => __('Certificate issued'), 'group' => 'outputs', 'default' => true],
            'id_card_status' => ['label' => __('ID card status'), 'group' => 'outputs', 'default' => false],
            'id_card_expires_at' => ['label' => __('ID card expires'), 'group' => 'outputs', 'default' => false],
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaultColumns(): array
    {
        return array_keys(array_filter(
            self::availableColumns(),
            fn (array $column) => $column['default']
        ));
    }

    /**
     * @return list<string>
     */
    public static function resolveColumns(?array $requested): array
    {
        $available = array_keys(self::availableColumns());
        $requested = array_values(array_intersect($requested ?? [], $available));

        return $requested !== [] ? $requested : self::defaultColumns();
    }

    public static function query(Request $request): Builder
    {
        $query = TrainingApplication::query()
            ->with(['course', 'warehouseIdentityCard'])
            ->where('status', 'payment_completed')
            ->where('application_review_status', 'approved')
            ->whereNotNull('account_verified_at')
            ->whereNotNull('payment_verified_at')
            ->where('exam_passed', true)
            ->whereNotNull('registration_number');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('session_year')) {
            $query->whereHas('course', fn ($q) => $q->where('session_year', $request->integer('session_year')));
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->string('gender')->toString());
        }

        if ($request->filled('region')) {
            $query->where('region', $request->string('region')->toString());
        }

        if ($request->filled('certificate_status')) {
            if ($request->string('certificate_status')->toString() === 'issued') {
                $query->whereNotNull('certificate_issued_at');
            } elseif ($request->string('certificate_status')->toString() === 'not_issued') {
                $query->whereNull('certificate_issued_at');
            }
        }

        if ($request->filled('id_card_status')) {
            $status = $request->string('id_card_status')->toString();
            if ($status === 'none') {
                $query->whereDoesntHave('warehouseIdentityCard');
            } else {
                $query->whereHas('warehouseIdentityCard', fn ($q) => $q->where('status', $status));
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('payment_completed_at', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('payment_completed_at', '<=', $request->date('to_date'));
        }

        return $query->orderBy('registration_number');
    }

    /**
     * @param  list<string>  $columns
     */
    public static function exportExcel(Request $request, array $columns): StreamedResponse
    {
        $applications = self::query($request)->get();
        $definitions = self::availableColumns();
        $filename = 'trained-users-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($applications, $columns, $definitions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, array_map(
                fn (string $key) => $definitions[$key]['label'],
                $columns
            ));

            foreach ($applications as $application) {
                fputcsv($handle, array_map(
                    fn (string $key) => self::value($application, $key),
                    $columns
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function value(TrainingApplication $application, string $key): string
    {
        return match ($key) {
            'full_name' => trim(collect([
                $application->first_name,
                $application->middle_name,
                $application->last_name,
            ])->filter()->implode(' ')),
            'gender' => $application->gender ? __(ucfirst($application->gender)) : '',
            'date_of_birth' => $application->date_of_birth?->format('Y-m-d') ?? '',
            'position' => TrainingApplication::positionLabel($application->position) ?? ($application->position ?? ''),
            'company_or_private' => $application->company_or_private ? __(ucfirst($application->company_or_private)) : '',
            'course_name' => $application->course?->name ?? '',
            'session_year' => (string) ($application->course?->session_year ?? ''),
            'trained_year' => (string) ($application->trained_year ?? ''),
            'application_type' => $application->application_type === 'legacy_expert'
                ? __('Legacy trained person')
                : __('New applicant'),
            'exam_score' => $application->exam_score !== null
                ? number_format((float) $application->exam_score, 2)
                : '',
            'exam_passed' => $application->exam_passed === true
                ? __('Yes')
                : ($application->exam_passed === false ? __('No') : ''),
            'exam_results_published_at' => $application->exam_results_published_at?->format('Y-m-d') ?? '',
            'certificate_issued_at' => $application->certificate_issued_at?->format('Y-m-d') ?? '',
            'id_card_status' => $application->warehouseIdentityCard
                ? $application->warehouseIdentityCard->statusLabel()
                : __('None'),
            'id_card_expires_at' => $application->warehouseIdentityCard?->expires_at?->format('Y-m-d') ?? '',
            default => (string) ($application->{$key} ?? ''),
        };
    }

}
