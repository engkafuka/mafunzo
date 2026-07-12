<?php

namespace App\Support;

use App\Models\TrainingApplication;

class TraineeProgressTracker
{
    /**
     * Single current status for one training application (shown on My Applications).
     *
     * @return array{label: string, tone: string}
     */
    public static function currentStatus(TrainingApplication $application): array
    {
        $application->loadMissing(['warehouseIdentityCard', 'user']);

        $card = $application->warehouseIdentityCard;

        if ($card?->isPublished()) {
            return [
                'label' => __('Identity card published'),
                'tone' => 'complete',
            ];
        }

        if ($application->isEligibleForIdentityCard() || $card?->isDraft()) {
            return [
                'label' => __('Waiting for identity card'),
                'tone' => 'waiting',
            ];
        }

        if ($application->hasPublishedExamResults()) {
            if ($application->exam_passed === true) {
                return [
                    'label' => __('Exam passed'),
                    'tone' => 'complete',
                ];
            }

            return [
                'label' => __('Exam results published'),
                'tone' => 'complete',
            ];
        }

        if ($application->hasRecordedExamResults()) {
            return [
                'label' => __('Exam recorded — awaiting publication'),
                'tone' => 'waiting',
            ];
        }

        $fullyVerified = $application->application_review_status === 'approved'
            && $application->account_verified_at
            && $application->payment_verified_at;

        if ($fullyVerified) {
            return [
                'label' => __('Staff verified — awaiting exam'),
                'tone' => 'waiting',
            ];
        }

        if ($application->application_review_status === 'rejected') {
            return [
                'label' => __('Application rejected'),
                'tone' => 'rejected',
            ];
        }

        if ($application->payment_completed_at || $application->status === 'payment_completed') {
            return [
                'label' => __('Payment confirmed — awaiting staff verification'),
                'tone' => 'waiting',
            ];
        }

        if ($application->status === 'pending_payment') {
            return [
                'label' => __('Awaiting payment confirmation'),
                'tone' => 'current',
            ];
        }

        if ($application->application_review_status === 'pending') {
            return [
                'label' => __('Awaiting application review'),
                'tone' => 'waiting',
            ];
        }

        return [
            'label' => __('Application in progress'),
            'tone' => 'pending',
        ];
    }
}
