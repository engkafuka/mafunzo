<?php

namespace App\Support\Interview;

use App\Models\InterviewCompany;
use App\Models\InterviewSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewCompanyReport
{
    /**
     * Session statuses treated as "conducted" by default (exclude draft / scheduled / cancelled).
     *
     * @return list<string>
     */
    public static function conductedStatuses(): array
    {
        return [
            InterviewSession::STATUS_IN_PROGRESS,
            InterviewSession::STATUS_SCORING,
            InterviewSession::STATUS_UNDER_REVIEW,
            InterviewSession::STATUS_COMPLETED,
        ];
    }

    public static function query(Request $request): Builder
    {
        $query = InterviewSession::query()
            ->with(['company', 'result'])
            ->orderByDesc('interview_date')
            ->orderByDesc('id');

        $statuses = self::resolveStatuses($request);
        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('interview_type')) {
            $query->where('interview_type', $request->string('interview_type')->toString());
        }

        if ($request->filled('passed')) {
            $passed = $request->string('passed')->toString();
            if ($passed === 'yes') {
                $query->whereHas('result', fn ($q) => $q->where('passed', true));
            } elseif ($passed === 'no') {
                $query->whereHas('result', fn ($q) => $q->where('passed', false));
            } elseif ($passed === 'pending') {
                $query->whereDoesntHave('result');
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('interview_date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('interview_date', '<=', $request->date('to_date'));
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public static function resolveStatuses(Request $request): array
    {
        $allowed = array_keys(config('interview.session_statuses', []));

        // No status param → default to conducted interviews.
        if (! $request->has('status')) {
            return self::conductedStatuses();
        }

        $raw = trim((string) $request->input('status'));

        // Explicit empty → all statuses (no status filter).
        if ($raw === '') {
            return [];
        }

        if ($raw === 'conducted') {
            return self::conductedStatuses();
        }

        return in_array($raw, $allowed, true) ? [$raw] : self::conductedStatuses();
    }

    public static function rows(Request $request): Collection
    {
        return self::query($request)->get();
    }

    public static function exportCsv(Request $request): StreamedResponse
    {
        $sessions = self::rows($request);
        $filename = 'interview-company-register-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sessions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('Date'),
                __('Session code'),
                __('Company'),
                __('Registration number'),
                __('Interviewee'),
                __('Title'),
                __('Type'),
                __('Status'),
                __('Score %'),
                __('Pass'),
                __('Recommendation'),
                __('Decision'),
            ]);

            foreach ($sessions as $session) {
                fputcsv($handle, [
                    $session->interview_date?->format('Y-m-d') ?? '',
                    $session->session_code,
                    $session->company?->name ?? '',
                    $session->company?->registration_number ?? '',
                    $session->interviewee_name,
                    $session->interviewee_title ?? '',
                    $session->typeLabel(),
                    $session->statusLabel(),
                    $session->result?->percentage !== null
                        ? number_format((float) $session->result->percentage, 2)
                        : '',
                    self::passLabel($session),
                    self::recommendationLabel($session->result?->recommendation),
                    self::decisionLabel($session->result?->decision_status),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function exportPdf(Request $request): Response
    {
        $sessions = self::rows($request);
        $filename = 'interview-company-register-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('exports.interview-company-register-pdf', [
            'sessions' => $sessions,
            'filters' => self::filterSummary($request),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    /**
     * @return array<string, string>
     */
    public static function filterSummary(Request $request): array
    {
        $summary = [];

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $from = $request->input('from_date') ?: '…';
            $to = $request->input('to_date') ?: '…';
            $summary[__('Date range')] = $from.' → '.$to;
        }

        if ($request->filled('company_id')) {
            $summary[__('Company')] = InterviewCompany::query()
                ->whereKey($request->integer('company_id'))
                ->value('name') ?: (string) $request->integer('company_id');
        }

        $status = $request->has('status') ? (string) $request->input('status') : 'conducted';
        $summary[__('Status')] = match (true) {
            $status === '' => __('All statuses'),
            $status === 'conducted' => __('Conducted'),
            default => config('interview.session_statuses.'.$status) ?? $status,
        };

        if ($request->filled('interview_type')) {
            $type = $request->string('interview_type')->toString();
            $summary[__('Type')] = config('interview.interview_types.'.$type) ?? $type;
        }

        if ($request->filled('passed')) {
            $summary[__('Pass')] = match ($request->string('passed')->toString()) {
                'yes' => __('Pass'),
                'no' => __('Fail'),
                'pending' => __('No result yet'),
                default => $request->string('passed')->toString(),
            };
        }

        return $summary;
    }

    public static function passLabel(InterviewSession $session): string
    {
        if (! $session->result) {
            return __('—');
        }

        return $session->result->passed ? __('Pass') : __('Fail');
    }

    public static function recommendationLabel(?string $recommendation): string
    {
        return match ($recommendation) {
            'recommend' => __('Recommend approval'),
            'do_not_recommend' => __('Do not recommend'),
            'conditional' => __('Conditional recommendation'),
            null, '' => __('—'),
            default => str_replace('_', ' ', $recommendation),
        };
    }

    public static function decisionLabel(?string $decision): string
    {
        return match ($decision) {
            'pending' => __('Pending'),
            'confirmed' => __('Confirmed'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            null, '' => __('—'),
            default => str_replace('_', ' ', $decision),
        };
    }
}
