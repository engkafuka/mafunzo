<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewCompany;
use App\Models\InterviewSession;
use App\Support\Interview\InterviewAuditExtractReport;
use App\Support\Interview\InterviewCompanyReport;
use App\Support\Interview\InterviewPanelAttendanceReport;
use App\Support\Interview\InterviewPanelCommentsReport;
use App\Support\Interview\InterviewPassRateByCompanyReport;
use App\Models\User;
use App\Support\PaginationHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('interview.reports.index');
    }

    public function companyRegister(Request $request): View
    {
        $this->validateSessionFilters($request);
        $this->applyFirstVisitDefaults($request, withStatus: true);

        $sessions = InterviewCompanyReport::query($request)
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        return view('interview.reports.company-register', [
            'sessions' => $sessions,
            'companies' => $this->companies(),
            'sessionStatuses' => config('interview.session_statuses', []),
            'interviewTypes' => config('interview.interview_types', []),
        ]);
    }

    public function companyRegisterCsv(Request $request): StreamedResponse
    {
        $this->validateSessionFilters($request);
        $this->applyExportDefaults($request, withStatus: true);

        return InterviewCompanyReport::exportCsv($request);
    }

    public function companyRegisterPdf(Request $request): Response
    {
        $this->validateSessionFilters($request);
        $this->applyExportDefaults($request, withStatus: true);

        return InterviewCompanyReport::exportPdf($request);
    }

    public function panelAttendance(Request $request): View
    {
        $this->validatePanelFilters($request);
        $this->applyFirstVisitDefaults($request, withStatus: true);

        $rows = InterviewPanelAttendanceReport::query($request)
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        return view('interview.reports.panel-attendance', [
            'rows' => $rows,
            'companies' => $this->companies(),
            'sessionOptions' => $this->sessionOptions(),
            'sessionStatuses' => config('interview.session_statuses', []),
        ]);
    }

    public function panelAttendanceCsv(Request $request): StreamedResponse
    {
        $this->validatePanelFilters($request);
        $this->applyExportDefaults($request, withStatus: true);

        return InterviewPanelAttendanceReport::exportCsv($request);
    }

    public function panelAttendancePdf(Request $request): Response
    {
        $this->validatePanelFilters($request);
        $this->applyExportDefaults($request, withStatus: true);

        return InterviewPanelAttendanceReport::exportPdf($request);
    }

    public function auditExtract(Request $request): View
    {
        $this->validateAuditFilters($request);
        $this->applyFirstVisitDefaults($request, withStatus: false);

        $rows = InterviewAuditExtractReport::query($request)
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        return view('interview.reports.audit-extract', [
            'rows' => $rows,
            'sessionOptions' => $this->sessionOptions(),
            'actions' => InterviewAuditExtractReport::availableActions(),
        ]);
    }

    public function auditExtractCsv(Request $request): StreamedResponse
    {
        $this->validateAuditFilters($request);
        $this->applyExportDefaults($request, withStatus: false);

        return InterviewAuditExtractReport::exportCsv($request);
    }

    public function auditExtractPdf(Request $request): Response
    {
        $this->validateAuditFilters($request);
        $this->applyExportDefaults($request, withStatus: false);

        return InterviewAuditExtractReport::exportPdf($request);
    }

    public function passRate(Request $request): View
    {
        $this->validateSessionFilters($request);

        if ($request->query->count() === 0) {
            $request->merge(['status' => 'conducted']);
        }

        $rows = InterviewPassRateByCompanyReport::rows($request);

        return view('interview.reports.pass-rate', [
            'rows' => $rows,
            'companies' => $this->companies(),
            'sessionStatuses' => config('interview.session_statuses', []),
            'interviewTypes' => config('interview.interview_types', []),
        ]);
    }

    public function passRateCsv(Request $request): StreamedResponse
    {
        $this->validateSessionFilters($request);
        if (! $request->has('status')) {
            $request->merge(['status' => 'conducted']);
        }

        return InterviewPassRateByCompanyReport::exportCsv($request);
    }

    public function passRatePdf(Request $request): Response
    {
        $this->validateSessionFilters($request);
        if (! $request->has('status')) {
            $request->merge(['status' => 'conducted']);
        }

        return InterviewPassRateByCompanyReport::exportPdf($request);
    }

    public function panelComments(Request $request): View
    {
        $this->validatePanelCommentsFilters($request);
        $this->applyPanelCommentsDefaults($request);

        $view = InterviewPanelCommentsReport::resolveView($request);

        $data = [
            'companies' => $this->companies(),
            'sessionOptions' => $this->sessionOptions(),
            'sessionStatuses' => config('interview.session_statuses', []),
            'panelistOptions' => $this->panelistOptions(),
            'viewMode' => $view,
            'commentsOnly' => InterviewPanelCommentsReport::commentsOnly($request),
        ];

        if ($view === InterviewPanelCommentsReport::VIEW_SESSION) {
            if ($request->filled('session_id')) {
                $session = InterviewSession::query()
                    ->with(['company', 'result', 'panelists.user'])
                    ->findOrFail($request->integer('session_id'));

                $data['session'] = $session;
                $data['groupedScores'] = InterviewPanelCommentsReport::scoresGroupedByPanelist($session, $request);
            }
        } elseif ($view === InterviewPanelCommentsReport::VIEW_GROUPED) {
            $data['sessions'] = InterviewPanelCommentsReport::sessionsQuery($request)
                ->with(['company', 'result', 'panelists.user'])
                ->paginate(PaginationHelper::PER_PAGE)
                ->withQueryString();

            $data['groupedBySession'] = $data['sessions']->getCollection()->mapWithKeys(
                fn (InterviewSession $session) => [
                    $session->id => InterviewPanelCommentsReport::scoresGroupedByPanelist($session, $request),
                ]
            );
        } else {
            $data['rows'] = InterviewPanelCommentsReport::query($request)
                ->paginate(PaginationHelper::PER_PAGE)
                ->withQueryString();
        }

        return view('interview.reports.panel-comments', $data);
    }

    public function panelCommentsCsv(Request $request): StreamedResponse
    {
        $this->validatePanelCommentsFilters($request);
        $this->applyPanelCommentsExportDefaults($request);

        return InterviewPanelCommentsReport::exportCsv($request);
    }

    public function panelCommentsPdf(Request $request): Response
    {
        $this->validatePanelCommentsFilters($request);
        $this->applyPanelCommentsExportDefaults($request);

        return InterviewPanelCommentsReport::exportPdf($request);
    }

    private function companies()
    {
        return InterviewCompany::query()
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);
    }

    private function sessionOptions()
    {
        return InterviewSession::query()
            ->with('company:id,name')
            ->orderByDesc('interview_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'session_code', 'company_id', 'interview_date']);
    }

    private function applyFirstVisitDefaults(Request $request, bool $withStatus): void
    {
        if ($request->query->count() === 0) {
            $defaults = [
                'from_date' => now()->toDateString(),
                'to_date' => now()->toDateString(),
            ];
            if ($withStatus) {
                $defaults['status'] = 'conducted';
            }
            $request->merge($defaults);
        }
    }

    private function applyExportDefaults(Request $request, bool $withStatus): void
    {
        if ($withStatus && ! $request->has('status')) {
            $request->merge(['status' => 'conducted']);
        }

        if (! $request->filled('from_date') && ! $request->filled('to_date') && ! $request->filled('company_id') && ! $request->filled('session_id')) {
            $request->merge([
                'from_date' => now()->toDateString(),
                'to_date' => now()->toDateString(),
            ]);
        }
    }

    private function validateSessionFilters(Request $request): void
    {
        $statusKeys = array_merge(['', 'conducted'], array_keys(config('interview.session_statuses', [])));

        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'company_id' => ['nullable', 'integer', 'exists:interview_companies,id'],
            'status' => ['nullable', 'string', 'in:'.implode(',', $statusKeys)],
            'interview_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('interview.interview_types', [])))],
            'passed' => ['nullable', 'in:yes,no,pending'],
        ]);
    }

    private function validatePanelFilters(Request $request): void
    {
        $this->validateSessionFilters($request);

        $request->validate([
            'session_id' => ['nullable', 'integer', 'exists:interview_sessions,id'],
            'submission_status' => ['nullable', 'in:pending,submitted'],
        ]);
    }

    private function validateAuditFilters(Request $request): void
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'session_id' => ['nullable', 'integer', 'exists:interview_sessions,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);
    }

    private function validatePanelCommentsFilters(Request $request): void
    {
        $this->validatePanelFilters($request);

        $request->validate([
            'view' => ['nullable', 'in:table,grouped,session'],
            'comments_only' => ['nullable', 'boolean'],
            'panelist_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    private function applyPanelCommentsDefaults(Request $request): void
    {
        if ($request->query->count() === 0) {
            $request->merge([
                'from_date' => now()->toDateString(),
                'to_date' => now()->toDateString(),
                'status' => 'conducted',
                'comments_only' => '1',
                'view' => InterviewPanelCommentsReport::VIEW_TABLE,
            ]);
        } elseif (! $request->has('comments_only')) {
            $request->merge(['comments_only' => '1']);
        }
    }

    private function applyPanelCommentsExportDefaults(Request $request): void
    {
        $this->applyExportDefaults($request, withStatus: true);

        if (! $request->has('comments_only')) {
            $request->merge(['comments_only' => '1']);
        }
    }

    private function panelistOptions()
    {
        return User::query()
            ->whereHas('interviewRoles', fn ($q) => $q->whereIn('role', ['panelist', 'chair']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
