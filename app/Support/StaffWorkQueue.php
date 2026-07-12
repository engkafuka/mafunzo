<?php

namespace App\Support;

use App\Models\TrainingApplication;
use App\Models\User;
use App\Models\WarehouseIdentityCard;
use App\Support\IdentityCardService;

class StaffWorkQueue
{
    /**
     * @return array{
     *     pending_registrations: int,
     *     pending_application_reviews: int,
     *     pending_payment_verifications: int,
     *     unpublished_exam_results: int,
     *     eligible_id_cards: int,
     *     draft_id_cards: int
     * }
     */
    public static function counts(): array
    {
        return [
            'pending_registrations' => User::query()
                ->where('role', 'trainee')
                ->where('registration_status', 'pending')
                ->count(),
            'pending_application_reviews' => TrainingApplication::query()
                ->where('application_review_status', 'pending')
                ->whereIn('status', ['pending_payment', 'payment_completed'])
                ->count(),
            'pending_payment_verifications' => TrainingApplication::query()
                ->whereNull('payment_verified_at')
                ->where(function ($q) {
                    $q->where('status', 'payment_completed')
                        ->orWhereNotNull('payment_completed_at');
                })
                ->count(),
            'unpublished_exam_results' => TrainingApplication::query()
                ->whereNotNull('exam_uploaded_at')
                ->whereNull('exam_results_published_at')
                ->count(),
            'eligible_id_cards' => IdentityCardService::eligibleApplicationsQuery()
                ->whereDoesntHave('warehouseIdentityCard')
                ->count(),
            'draft_id_cards' => WarehouseIdentityCard::query()
                ->where('status', WarehouseIdentityCard::STATUS_DRAFT)
                ->count(),
        ];
    }
}
