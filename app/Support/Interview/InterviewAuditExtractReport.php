<?php

namespace App\Support\Interview;

use App\Models\InterviewAuditLog;
use App\Models\InterviewSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewAuditExtractReport
{
    public static function query(Request $request): Builder
    {
        $query = InterviewAuditLog::query()
            ->with(['user', 'session.company'])
            ->latest('id');

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->date('to_date'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->integer('session_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(function ($q) use ($term) {
                $q->where('action', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term);
            });
        }

        return $query;
    }

    public static function rows(Request $request): Collection
    {
        return self::query($request)->get();
    }

    /**
     * @return list<string>
     */
    public static function availableActions(): array
    {
        return InterviewAuditLog::query()
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    public static function exportCsv(Request $request): StreamedResponse
    {
        $rows = self::rows($request);
        $filename = 'interview-audit-extract-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('Timestamp'),
                __('Action'),
                __('Description'),
                __('User'),
                __('Session code'),
                __('Company'),
                __('Meta'),
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->created_at?->format('Y-m-d H:i:s') ?? '',
                    $row->action,
                    $row->description,
                    $row->user?->name ?? __('System'),
                    $row->session?->session_code ?? '',
                    $row->session?->company?->name ?? '',
                    $row->meta ? json_encode($row->meta, JSON_UNESCAPED_UNICODE) : '',
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
        $filename = 'interview-audit-extract-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('exports.interview-audit-extract-pdf', [
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
        $summary = [];

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $from = $request->input('from_date') ?: '…';
            $to = $request->input('to_date') ?: '…';
            $summary[__('Date range')] = $from.' → '.$to;
        }

        if ($request->filled('session_id')) {
            $code = InterviewSession::query()->whereKey($request->integer('session_id'))->value('session_code');
            $summary[__('Session')] = $code ?: (string) $request->integer('session_id');
        }

        if ($request->filled('action')) {
            $summary[__('Action')] = $request->string('action')->toString();
        }

        if ($request->filled('q')) {
            $summary[__('Search')] = $request->string('q')->toString();
        }

        return $summary;
    }
}
