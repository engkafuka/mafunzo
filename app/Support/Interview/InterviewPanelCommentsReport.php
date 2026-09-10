<?php

namespace App\Support\Interview;

use App\Models\InterviewScore;
use App\Models\InterviewSession;
use App\Models\InterviewSessionPanelist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewPanelCommentsReport
{
    public const VIEW_TABLE = 'table';

    public const VIEW_GROUPED = 'grouped';

    public const VIEW_SESSION = 'session';

    public static function commentsOnly(Request $request): bool
    {
        if (! $request->has('comments_only')) {
            return true;
        }

        return $request->boolean('comments_only');
    }

    public static function resolveView(Request $request): string
    {
        $view = $request->string('view')->toString();

        if (in_array($view, [self::VIEW_TABLE, self::VIEW_GROUPED, self::VIEW_SESSION], true)) {
            return $view;
        }

        return self::VIEW_TABLE;
    }

    public static function query(Request $request): Builder
    {
        $query = InterviewScore::query()
            ->select('interview_scores.*')
            ->join('interview_sessions', 'interview_sessions.id', '=', 'interview_scores.session_id')
            ->join('interview_questions', 'interview_questions.id', '=', 'interview_scores.question_id')
            ->with(['session.company', 'session.result', 'question', 'panelist'])
            ->where('interview_scores.status', 'submitted');

        self::applyScoreFilters($query, $request);
        self::applySessionFilters($query, $request);

        if ($request->filled('panelist_user_id')) {
            $query->where('interview_scores.panelist_user_id', $request->integer('panelist_user_id'));
        }

        return $query
            ->orderByDesc('interview_sessions.interview_date')
            ->orderBy('interview_scores.session_id')
            ->orderBy('interview_questions.sort_order')
            ->orderBy('interview_scores.panelist_user_id');
    }

    public static function sessionsQuery(Request $request): Builder
    {
        $query = InterviewSession::query()
            ->with(['company', 'result'])
            ->whereHas('scores', fn (Builder $q) => self::applyScoreFilters($q, $request));

        $statuses = InterviewCompanyReport::resolveStatuses($request);
        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('interview_date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('interview_date', '<=', $request->date('to_date'));
        }

        if ($request->filled('session_id')) {
            $query->whereKey($request->integer('session_id'));
        }

        return $query->orderByDesc('interview_date')->orderByDesc('id');
    }

    public static function applyScoreFilters(Builder $query, Request $request): void
    {
        $query->where('interview_scores.status', 'submitted');

        if (self::commentsOnly($request)) {
            $query->whereNotNull('interview_scores.comment')
                ->where('interview_scores.comment', '!=', '');
        }
    }

    public static function applySessionFilters(Builder $query, Request $request): void
    {
        InterviewPanelAttendanceReport::applySessionFilters($query, $request);
    }

    public static function rows(Request $request): Collection
    {
        return self::query($request)->get();
    }

    /** @return Collection<int, Collection<int, InterviewScore>> */
    public static function scoresGroupedByPanelist(InterviewSession $session, Request $request): Collection
    {
        $query = $session->scores()
            ->with(['question', 'panelist'])
            ->where('status', 'submitted');

        self::applyScoreFilters($query, $request);

        if ($request->filled('panelist_user_id')) {
            $query->where('panelist_user_id', $request->integer('panelist_user_id'));
        }

        return $query
            ->get()
            ->sortBy(fn (InterviewScore $score) => sprintf(
                '%05d-%05d',
                $score->panelist_user_id,
                $score->question?->sort_order ?? 0
            ))
            ->groupBy('panelist_user_id');
    }

    public static function panelistRole(InterviewSession $session, int $userId): string
    {
        $assignment = $session->relationLoaded('panelists')
            ? $session->panelists->firstWhere('user_id', $userId)
            : InterviewSessionPanelist::query()
                ->where('session_id', $session->id)
                ->where('user_id', $userId)
                ->first();

        return $assignment?->is_chair ? __('Chair') : __('Panelist');
    }

    public static function exportCsv(Request $request): StreamedResponse
    {
        $rows = self::rows($request);
        $filename = 'interview-panel-comments-'.now()->format('Ymd-His').'.csv';

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
                __('Q#'),
                __('Category'),
                __('Question'),
                __('Score'),
                __('Comment'),
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->session?->interview_date?->format('Y-m-d') ?? '',
                    $row->session?->session_code ?? '',
                    $row->session?->company?->name ?? '',
                    $row->session?->interviewee_name ?? '',
                    $row->panelist?->name ?? '',
                    $row->panelist?->email ?? '',
                    $row->session ? self::panelistRole($row->session, (int) $row->panelist_user_id) : '',
                    $row->question?->sort_order ?? '',
                    $row->question?->categoryLabel() ?? '',
                    $row->question?->question_text ?? '',
                    $row->score ?? '',
                    $row->comment ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function exportPdf(Request $request): Response
    {
        $sessions = self::sessionsQuery($request)
            ->with(['company', 'result', 'panelists.user'])
            ->get();

        $grouped = $sessions->mapWithKeys(function (InterviewSession $session) use ($request) {
            return [$session->id => self::scoresGroupedByPanelist($session, $request)];
        });

        $filename = 'interview-panel-comments-'.now()->format('Ymd-His').'.pdf';

        return Pdf::loadView('exports.interview-panel-comments-pdf', [
            'sessions' => $sessions,
            'grouped' => $grouped,
            'filters' => self::filterSummary($request),
            'generatedAt' => now(),
            'commentsOnly' => self::commentsOnly($request),
        ])->setPaper('a4', 'portrait')->download($filename);
    }

    /**
     * @return array<string, string>
     */
    public static function filterSummary(Request $request): array
    {
        $summary = InterviewPanelAttendanceReport::filterSummary($request);
        $summary[__('View')] = match (self::resolveView($request)) {
            self::VIEW_GROUPED => __('By session'),
            self::VIEW_SESSION => __('Session detail'),
            default => __('Table'),
        };
        $summary[__('Comments')] = self::commentsOnly($request)
            ? __('Comments only')
            : __('All questions');

        if ($request->filled('panelist_user_id')) {
            $name = \App\Models\User::query()->whereKey($request->integer('panelist_user_id'))->value('name');
            $summary[__('Panelist')] = $name ?: (string) $request->integer('panelist_user_id');
        }

        return $summary;
    }
}
