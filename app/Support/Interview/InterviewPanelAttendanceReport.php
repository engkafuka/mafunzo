<?php

namespace App\Support\Interview;

use App\Models\InterviewSession;
use App\Models\InterviewSessionPanelist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewPanelAttendanceReport
{
    public static function query(Request $request): Builder
    {
        $query = InterviewSessionPanelist::query()
            ->select('interview_session_panelists.*')
            ->join('interview_sessions', 'interview_sessions.id', '=', 'interview_session_panelists.session_id')
            ->with(['user', 'session.company'])
            ->where(function ($q) use ($request) {
                self::applySessionFilters($q, $request);
            });

        if ($request->filled('session_id')) {
            $query->where('interview_session_panelists.session_id', $request->integer('session_id'));
        }

        if ($request->filled('submission_status')) {
            $query->where('interview_session_panelists.submission_status', $request->string('submission_status')->toString());
        }

        return $query
            ->orderByDesc('interview_sessions.interview_date')
            ->orderBy('interview_session_panelists.session_id')
            ->orderByDesc('interview_session_panelists.is_chair')
            ->orderBy('interview_session_panelists.id');
    }

    public static function applySessionFilters(Builder $query, Request $request): void
    {
        // When called from join context, qualify columns with interview_sessions.
        $statuses = InterviewCompanyReport::resolveStatuses($request);
        if ($statuses !== []) {
            $query->whereIn('interview_sessions.status', $statuses);
        }

        if ($request->filled('company_id')) {
            $query->where('interview_sessions.company_id', $request->integer('company_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('interview_sessions.interview_date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('interview_sessions.interview_date', '<=', $request->date('to_date'));
        }

        if ($request->filled('session_id')) {
            $query->where('interview_sessions.id', $request->integer('session_id'));
        }
    }

    public static function rows(Request $request): Collection
    {
        return self::query($request)->get();
    }

    public static function exportCsv(Request $request): StreamedResponse
    {
        $rows = self::rows($request);
        $filename = 'interview-panel-attendance-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('Date'),
                __('Session code'),
                __('Company'),
                __('Interviewee'),
                __('Panelist'),
                __('Email'),
                __('Role'),
                __('Submission'),
                __('Submitted at'),
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->session?->interview_date?->format('Y-m-d') ?? '',
                    $row->session?->session_code ?? '',
                    $row->session?->company?->name ?? '',
                    $row->session?->interviewee_name ?? '',
                    $row->user?->name ?? '',
                    $row->user?->email ?? '',
                    $row->is_chair ? __('Chair') : __('Panelist'),
                    self::submissionLabel($row->submission_status),
                    $row->submitted_at?->format('Y-m-d H:i') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function exportPdf(Request $request): Response
    {
        $rows = self::rows($request);
        $filename = 'interview-panel-attendance-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('exports.interview-panel-attendance-pdf', [
            'rows' => $rows,
            'filters' => self::filterSummary($request),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    /**
     * @return array<string, string>
     */
    public static function filterSummary(Request $request): array
    {
        $summary = InterviewCompanyReport::filterSummary($request);

        if ($request->filled('session_id')) {
            $code = InterviewSession::query()->whereKey($request->integer('session_id'))->value('session_code');
            $summary[__('Session')] = $code ?: (string) $request->integer('session_id');
        }

        if ($request->filled('submission_status')) {
            $summary[__('Submission')] = self::submissionLabel($request->string('submission_status')->toString());
        }

        return $summary;
    }

    public static function submissionLabel(?string $status): string
    {
        return match ($status) {
            'submitted' => __('Submitted'),
            'pending' => __('Pending'),
            default => $status ? str_replace('_', ' ', $status) : __('—'),
        };
    }
}
