<?php

namespace App\Support\Interview;

use App\Models\InterviewSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewPassRateByCompanyReport
{
    public static function rows(Request $request): Collection
    {
        $sessionQuery = InterviewSession::query()->select('id', 'company_id');

        $statuses = InterviewCompanyReport::resolveStatuses($request);
        if ($statuses !== []) {
            $sessionQuery->whereIn('status', $statuses);
        }

        if ($request->filled('company_id')) {
            $sessionQuery->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('interview_type')) {
            $sessionQuery->where('interview_type', $request->string('interview_type')->toString());
        }

        if ($request->filled('from_date')) {
            $sessionQuery->whereDate('interview_date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $sessionQuery->whereDate('interview_date', '<=', $request->date('to_date'));
        }

        $sessionIds = $sessionQuery->pluck('id');

        if ($sessionIds->isEmpty()) {
            return collect();
        }

        $stats = DB::table('interview_sessions as s')
            ->leftJoin('interview_results as r', 'r.session_id', '=', 's.id')
            ->leftJoin('interview_companies as c', 'c.id', '=', 's.company_id')
            ->whereIn('s.id', $sessionIds)
            ->groupBy('s.company_id', 'c.name', 'c.registration_number')
            ->orderBy('c.name')
            ->select([
                's.company_id',
                'c.name as company_name',
                'c.registration_number',
                DB::raw('COUNT(s.id) as sessions_count'),
                DB::raw('SUM(CASE WHEN r.passed = true THEN 1 ELSE 0 END) as passed_count'),
                DB::raw('SUM(CASE WHEN r.passed = false THEN 1 ELSE 0 END) as failed_count'),
                DB::raw('SUM(CASE WHEN r.id IS NULL THEN 1 ELSE 0 END) as pending_count'),
                DB::raw('AVG(r.percentage) as avg_percentage'),
            ])
            ->get();

        return $stats->map(function ($row) {
            $scored = (int) $row->passed_count + (int) $row->failed_count;
            $passRate = $scored > 0
                ? round(((int) $row->passed_count / $scored) * 100, 1)
                : null;

            return (object) [
                'company_id' => $row->company_id,
                'company_name' => $row->company_name,
                'registration_number' => $row->registration_number,
                'sessions_count' => (int) $row->sessions_count,
                'passed_count' => (int) $row->passed_count,
                'failed_count' => (int) $row->failed_count,
                'pending_count' => (int) $row->pending_count,
                'avg_percentage' => $row->avg_percentage !== null
                    ? round((float) $row->avg_percentage, 2)
                    : null,
                'pass_rate' => $passRate,
            ];
        });
    }

    public static function exportCsv(Request $request): StreamedResponse
    {
        $rows = self::rows($request);
        $filename = 'interview-pass-rate-by-company-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('Company'),
                __('Registration number'),
                __('Sessions'),
                __('Passed'),
                __('Failed'),
                __('No result'),
                __('Pass rate %'),
                __('Average score %'),
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->company_name ?? '',
                    $row->registration_number ?? '',
                    $row->sessions_count,
                    $row->passed_count,
                    $row->failed_count,
                    $row->pending_count,
                    $row->pass_rate !== null ? number_format($row->pass_rate, 1) : '',
                    $row->avg_percentage !== null ? number_format($row->avg_percentage, 2) : '',
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
        $filename = 'interview-pass-rate-by-company-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('exports.interview-pass-rate-by-company-pdf', [
            'rows' => $rows,
            'filters' => InterviewCompanyReport::filterSummary($request),
            'generatedAt' => now(),
            'totals' => [
                'sessions' => $rows->sum('sessions_count'),
                'passed' => $rows->sum('passed_count'),
                'failed' => $rows->sum('failed_count'),
                'pending' => $rows->sum('pending_count'),
            ],
        ])->setPaper('a4', 'portrait')->download($filename);
    }
}
