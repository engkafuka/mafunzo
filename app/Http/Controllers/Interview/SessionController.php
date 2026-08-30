<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewCompany;
use App\Models\InterviewQuestionSet;
use App\Models\InterviewSession;
use App\Models\InterviewUserRole;
use App\Models\User;
use App\Support\Interview\InterviewAuditLogger;
use App\Support\Interview\InterviewSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(
        private InterviewSessionService $sessionService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = InterviewSession::with(['company', 'questionSet', 'panelists'])
            ->latest('id');

        if (! $user->hasInterviewRole('admin', 'chair', 'approver', 'viewer')) {
            $query->whereHas('panelists', fn ($q) => $q->where('user_id', $user->id));
        }

        $sessions = $query->paginate(20);

        return view('interview.sessions.index', compact('sessions'));
    }

    public function create(): View
    {
        $companies = InterviewCompany::where('status', 'active')->orderBy('name')->get();
        $questionSets = InterviewQuestionSet::where('is_active', true)->orderBy('title')->get();
        $panelistCandidates = User::whereHas('interviewRoles', fn ($q) => $q->whereIn('role', [
            InterviewUserRole::ROLE_PANELIST,
            InterviewUserRole::ROLE_CHAIR,
        ]))->orderBy('name')->get();

        return view('interview.sessions.create', compact('companies', 'questionSets', 'panelistCandidates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:interview_companies,id'],
            'question_set_id' => ['required', 'exists:interview_question_sets,id'],
            'interviewee_name' => ['required', 'string', 'max:255'],
            'interviewee_title' => ['nullable', 'string', 'max:255'],
            'interview_type' => ['required', 'string'],
            'interview_date' => ['nullable', 'date'],
            'interview_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:255'],
            'pass_mark' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'panelist_ids' => ['required', 'array', 'min:1'],
            'panelist_ids.*' => ['integer', 'exists:users,id'],
            'chair_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $session = InterviewSession::create([
            'session_code' => $this->sessionService->generateSessionCode(),
            'company_id' => $data['company_id'],
            'question_set_id' => $data['question_set_id'],
            'interviewee_name' => $data['interviewee_name'],
            'interviewee_title' => $data['interviewee_title'] ?? null,
            'interview_type' => $data['interview_type'],
            'interview_date' => $data['interview_date'] ?? null,
            'interview_time' => $data['interview_time'] ?? null,
            'venue' => $data['venue'] ?? null,
            'pass_mark' => $data['pass_mark'] ?? config('interview.default_pass_mark'),
            'status' => InterviewSession::STATUS_SCHEDULED,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $this->sessionService->syncPanelists(
            $session,
            $data['panelist_ids'],
            isset($data['chair_user_id']) ? (int) $data['chair_user_id'] : null
        );

        InterviewAuditLogger::log('session_created', 'Interview session created', $request->user(), $session->id);

        return redirect()->route('interview.sessions.show', $session)->with('status', __('Interview session created.'));
    }

    public function show(InterviewSession $session, Request $request): View
    {
        $this->authorizeSessionAccess($request, $session);

        $session->load(['company', 'questionSet', 'panelists.user', 'result', 'creator']);

        return view('interview.sessions.show', compact('session'));
    }

    public function edit(InterviewSession $session, Request $request): View
    {
        abort_unless($request->user()->hasInterviewRole('admin'), 403);
        abort_if($session->status === InterviewSession::STATUS_COMPLETED, 403);

        $companies = InterviewCompany::where('status', 'active')->orderBy('name')->get();
        $questionSets = InterviewQuestionSet::where('is_active', true)->orderBy('title')->get();
        $panelistCandidates = User::whereHas('interviewRoles', fn ($q) => $q->whereIn('role', [
            InterviewUserRole::ROLE_PANELIST,
            InterviewUserRole::ROLE_CHAIR,
        ]))->orderBy('name')->get();

        $session->load('panelists');

        return view('interview.sessions.edit', compact('session', 'companies', 'questionSets', 'panelistCandidates'));
    }

    public function update(Request $request, InterviewSession $session): RedirectResponse
    {
        abort_unless($request->user()->hasInterviewRole('admin'), 403);
        abort_if($session->status === InterviewSession::STATUS_COMPLETED, 403);

        $data = $request->validate([
            'company_id' => ['required', 'exists:interview_companies,id'],
            'question_set_id' => ['required', 'exists:interview_question_sets,id'],
            'interviewee_name' => ['required', 'string', 'max:255'],
            'interviewee_title' => ['nullable', 'string', 'max:255'],
            'interview_type' => ['required', 'string'],
            'interview_date' => ['nullable', 'date'],
            'interview_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:255'],
            'pass_mark' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'panelist_ids' => ['required', 'array', 'min:1'],
            'panelist_ids.*' => ['integer', 'exists:users,id'],
            'chair_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $session->update([
            'company_id' => $data['company_id'],
            'question_set_id' => $data['question_set_id'],
            'interviewee_name' => $data['interviewee_name'],
            'interviewee_title' => $data['interviewee_title'] ?? null,
            'interview_type' => $data['interview_type'],
            'interview_date' => $data['interview_date'] ?? null,
            'interview_time' => $data['interview_time'] ?? null,
            'venue' => $data['venue'] ?? null,
            'pass_mark' => $data['pass_mark'] ?? config('interview.default_pass_mark'),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->sessionService->syncPanelists(
            $session,
            $data['panelist_ids'],
            isset($data['chair_user_id']) ? (int) $data['chair_user_id'] : null
        );

        return redirect()->route('interview.sessions.show', $session)->with('status', __('Interview session updated.'));
    }

    public function openScoring(Request $request, InterviewSession $session): RedirectResponse
    {
        abort_unless($request->user()->hasInterviewRole('admin'), 403);

        try {
            $this->sessionService->openScoring($session, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Scoring is now open for panelists.'));
    }

    private function authorizeSessionAccess(Request $request, InterviewSession $session): void
    {
        $user = $request->user();

        if ($user->hasInterviewRole('admin', 'chair', 'approver', 'viewer')) {
            return;
        }

        $assigned = $session->panelists()->where('user_id', $user->id)->exists();

        abort_unless($assigned, 403);
    }
}
