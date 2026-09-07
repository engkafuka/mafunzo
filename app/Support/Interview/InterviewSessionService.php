<?php

namespace App\Support\Interview;

use App\Models\InterviewSession;
use App\Models\InterviewSessionPanelist;
use App\Models\User;
use Illuminate\Support\Str;

class InterviewSessionService
{
    public function generateSessionCode(): string
    {
        do {
            $code = 'INT-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
        } while (InterviewSession::where('session_code', $code)->exists());

        return $code;
    }

    public function openScoring(InterviewSession $session, User $actor): void
    {
        if (! in_array($session->status, [
            InterviewSession::STATUS_DRAFT,
            InterviewSession::STATUS_SCHEDULED,
            InterviewSession::STATUS_IN_PROGRESS,
        ], true)) {
            throw new \RuntimeException('Session cannot be opened for scoring in its current status.');
        }

        if ($session->panelists()->count() === 0) {
            throw new \RuntimeException('Assign at least one panelist before opening scoring.');
        }

        $session->update([
            'status' => InterviewSession::STATUS_SCORING,
            'scoring_opened_at' => now(),
        ]);

        app(InterviewScoringService::class)->ensureScoreRows($session);

        InterviewAuditLogger::log(
            'scoring_opened',
            'Interview scoring opened',
            $actor,
            $session->id
        );
    }

    public function syncPanelists(InterviewSession $session, array $panelistUserIds, ?int $chairUserId): void
    {
        if (in_array($session->status, [
            InterviewSession::STATUS_COMPLETED,
            InterviewSession::STATUS_CANCELLED,
        ], true)) {
            throw new \RuntimeException('Cannot change panelists on a completed or cancelled session.');
        }

        $panelistUserIds = array_values(array_unique(array_map('intval', $panelistUserIds)));

        if ($panelistUserIds === []) {
            throw new \RuntimeException('Select at least one panelist.');
        }

        if ($chairUserId && ! in_array($chairUserId, $panelistUserIds, true)) {
            throw new \RuntimeException('Chairperson must be one of the assigned panelists.');
        }

        $existing = $session->panelists()->pluck('user_id')->all();
        $toRemove = array_diff($existing, $panelistUserIds);

        if ($toRemove !== []) {
            $submitted = $session->panelists()
                ->whereIn('user_id', $toRemove)
                ->where('submission_status', 'submitted')
                ->exists();

            if ($submitted) {
                throw new \RuntimeException('Cannot remove a panelist who has already submitted scores.');
            }

            $session->panelists()->whereIn('user_id', $toRemove)->delete();
            $session->scores()->whereIn('panelist_user_id', $toRemove)->delete();
        }

        foreach ($panelistUserIds as $userId) {
            InterviewSessionPanelist::updateOrCreate(
                ['session_id' => $session->id, 'user_id' => $userId],
                ['is_chair' => $chairUserId === $userId]
            );
        }

        $session->panelists()->whereNotIn('user_id', $panelistUserIds)->delete();
        $session->panelists()->update(['is_chair' => false]);

        if ($chairUserId) {
            $session->panelists()->where('user_id', $chairUserId)->update(['is_chair' => true]);
        }
    }

    public function confirmReview(
        InterviewSession $session,
        User $chair,
        string $recommendation,
        ?string $chairNotes
    ): void {
        if ($session->status !== InterviewSession::STATUS_UNDER_REVIEW) {
            throw new \RuntimeException('Session is not ready for chair review.');
        }

        $result = $session->result;

        if (! $result) {
            throw new \RuntimeException('Consolidated results are not available yet.');
        }

        $result->update([
            'recommendation' => $recommendation,
            'chair_notes' => $chairNotes,
            'decision_status' => 'confirmed',
            'reviewed_by' => $chair->id,
            'reviewed_at' => now(),
        ]);

        InterviewAuditLogger::log(
            'chair_review_confirmed',
            'Chairperson confirmed interview recommendation',
            $chair,
            $session->id,
            ['recommendation' => $recommendation]
        );
    }

    public function approveDecision(InterviewSession $session, User $approver, bool $approved): void
    {
        $result = $session->result;

        if (! $result || $result->decision_status !== 'confirmed') {
            throw new \RuntimeException('Chair review must be confirmed before approval.');
        }

        $result->update([
            'decision_status' => $approved ? 'approved' : 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        if ($approved) {
            $session->update([
                'status' => InterviewSession::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }

        InterviewAuditLogger::log(
            $approved ? 'decision_approved' : 'decision_rejected',
            $approved ? 'Interview decision approved' : 'Interview decision rejected',
            $approver,
            $session->id
        );
    }

    public function cancelSession(InterviewSession $session, User $actor, ?string $reason = null): void
    {
        if (! $session->canBeCancelled()) {
            throw new \RuntimeException('This session cannot be cancelled in its current status.');
        }

        $session->update([
            'status' => InterviewSession::STATUS_CANCELLED,
        ]);

        InterviewAuditLogger::log(
            'session_cancelled',
            'Interview session cancelled',
            $actor,
            $session->id,
            array_filter([
                'session_code' => $session->session_code,
                'previous_note' => $reason,
            ])
        );
    }

    public function deleteSession(InterviewSession $session, User $actor): void
    {
        if (! $session->canBeDeleted()) {
            throw new \RuntimeException('Only draft or scheduled sessions can be deleted. Cancel active sessions instead.');
        }

        $meta = [
            'session_code' => $session->session_code,
            'company_id' => $session->company_id,
            'interviewee_name' => $session->interviewee_name,
            'interview_date' => $session->interview_date?->format('Y-m-d'),
            'status' => $session->status,
        ];

        InterviewAuditLogger::log(
            'session_deleted',
            'Interview session permanently deleted',
            $actor,
            $session->id,
            $meta
        );

        $session->delete();
    }
}
