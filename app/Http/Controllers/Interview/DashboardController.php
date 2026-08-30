<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewAuditLog;
use App\Models\InterviewSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $sessionsQuery = InterviewSession::with(['company', 'questionSet'])
            ->latest('id');

        if (! $user->hasInterviewRole('admin', 'chair', 'approver', 'viewer')) {
            $sessionsQuery->whereHas('panelists', fn ($q) => $q->where('user_id', $user->id));
        }

        $sessions = $sessionsQuery->limit(20)->get();

        $pendingScoring = InterviewSession::where('status', InterviewSession::STATUS_SCORING)
            ->whereHas('panelists', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('submission_status', 'pending');
            })
            ->count();

        $underReview = InterviewSession::where('status', InterviewSession::STATUS_UNDER_REVIEW)->count();

        $recentAudit = InterviewAuditLog::with(['user', 'session'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('interview.dashboard', compact('sessions', 'pendingScoring', 'underReview', 'recentAudit'));
    }
}
