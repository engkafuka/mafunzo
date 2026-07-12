<?php

namespace App\Support;

use App\Models\TrainingApplication;
use App\Models\User;
use App\Models\WarehouseIdentityCard;
use App\Notifications\TraineeStatusNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdentityCardService
{
    public static function eligibleApplicationsQuery()
    {
        return TrainingApplication::query()
            ->with(['course', 'user', 'warehouseIdentityCard'])
            ->whereNotNull('registration_number')
            ->where('status', 'payment_completed')
            ->where('application_review_status', 'approved')
            ->whereNotNull('account_verified_at')
            ->whereNotNull('payment_verified_at')
            ->where('exam_passed', true)
            ->whereNotNull('exam_results_published_at')
            ->whereHas('user', fn ($query) => $query->whereNotNull('profile_photo_path'));
    }

    public static function generate(TrainingApplication $application, User $actor): WarehouseIdentityCard
    {
        if (! $application->isEligibleForIdentityCard()) {
            throw new \RuntimeException($application->identityCardIneligibilityReason() ?? __('Not eligible for identity card.'));
        }

        return DB::transaction(function () use ($application, $actor) {
            $application->loadMissing(['course', 'user']);

            $existing = $application->warehouseIdentityCard;
            if ($existing && $existing->isPublished()) {
                throw new \RuntimeException(__('A published identity card already exists for this application.'));
            }

            if ($existing) {
                self::deleteFiles($existing);
                $existing->delete();
            }

            $issuedAt = now()->startOfDay();
            $expiresAt = $issuedAt->copy()->addYears((int) config('identity_card.expiry_years', 3));

            $card = WarehouseIdentityCard::create([
                'training_application_id' => $application->id,
                'user_id' => $application->user_id,
                'verification_token' => WarehouseIdentityCard::generateVerificationToken(),
                'registration_number' => $application->registration_number,
                'session_year' => $application->course?->session_year,
                'trained_year' => $application->trained_year,
                'full_name' => trim($application->first_name.' '.($application->middle_name ?? '').' '.$application->last_name),
                'position' => TrainingApplication::positionLabel($application->position),
                'course_name' => $application->course?->name ?? __('Training course'),
                'company_name' => $application->company_name,
                'photo_path' => '',
                'status' => WarehouseIdentityCard::STATUS_DRAFT,
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ]);

            $photoPath = ProfilePhotoStorage::copySnapshot($application->user, $card->id);
            $card->update(['photo_path' => $photoPath]);

            $pdfPath = self::buildPdf($card);
            $card->update(['pdf_path' => $pdfPath]);

            AuditLogger::logAction(
                __('Generated warehouse identity card draft for :name', ['name' => $card->full_name]),
                $application,
            );

            return $card->fresh();
        });
    }

    public static function publish(WarehouseIdentityCard $card, User $actor): WarehouseIdentityCard
    {
        if (! $card->isDraft()) {
            throw new \RuntimeException(__('Only draft identity cards can be published.'));
        }

        $card->update(['pdf_path' => self::buildPdf($card)]);

        $card->update([
            'status' => WarehouseIdentityCard::STATUS_PUBLISHED,
            'published_by' => $actor->id,
            'published_at' => now(),
            'revoked_by' => null,
            'revoked_at' => null,
        ]);

        AuditLogger::logAction(
            __('Published warehouse identity card for :reg', ['reg' => $card->registration_number]),
            $card->trainingApplication,
        );

        $card->loadMissing('user');
        if ($card->user) {
            $card->user->notify(new TraineeStatusNotification(
                __('Identity card published'),
                __('Your warehouse identity card (:reg) is ready to download.', [
                    'reg' => $card->registration_number,
                ]),
                route('training.identity-cards'),
                __('Download ID card'),
            ));
        }

        return $card->fresh();
    }

    public static function revoke(WarehouseIdentityCard $card, User $actor): WarehouseIdentityCard
    {
        if (! $card->isPublished()) {
            throw new \RuntimeException(__('Only published identity cards can be revoked.'));
        }

        $card->update([
            'status' => WarehouseIdentityCard::STATUS_REVOKED,
            'revoked_by' => $actor->id,
            'revoked_at' => now(),
        ]);

        AuditLogger::logAction(
            __('Revoked warehouse identity card for :reg', ['reg' => $card->registration_number]),
            $card->trainingApplication,
        );

        return $card->fresh();
    }

    public static function buildPdf(WarehouseIdentityCard $card): string
    {
        $photoAbsolute = Storage::disk('local')->path($card->photo_path);
        $logoAbsolute = public_path('images/wrrblogo.png');

        $photoDataUri = PdfImageDataUri::jpegForPdf($photoAbsolute);
        $logoDataUri = PdfImageDataUri::jpegForPdf($logoAbsolute);

        if ($photoDataUri === null) {
            throw new \RuntimeException(__('Profile photo is missing for this identity card.'));
        }

        if ($logoDataUri === null) {
            throw new \RuntimeException(__('WRRB logo file is missing or could not be prepared for PDF. Enable the PHP GD extension or add public/images/wrrblogo.jpg.'));
        }

        $qrDataUri = QrCodeGenerator::pngDataUri($card->verificationUrl(), 96);

        // ISO CR80 card size: 85.6mm x 53.98mm (landscape).
        $pdf = Pdf::loadView('exports.warehouse-id-card', [
            'card' => $card,
            'photoDataUri' => $photoDataUri,
            'logoDataUri' => $logoDataUri,
            'qrDataUri' => $qrDataUri,
            'organization' => config('identity_card.organization'),
            'organizationSw' => config('identity_card.organization_sw'),
            'title' => config('identity_card.title'),
            'titleSw' => config('identity_card.title_sw'),
        ])->setPaper(
            [0, 0, config('identity_card.pdf_width_pt', 242.65), config('identity_card.pdf_height_pt', 153.07)],
            config('identity_card.pdf_orientation', 'portrait')
        )->setOptions([
            'dpi' => 72,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
        ]);

        $path = 'identity-cards/pdf/card-'.$card->id.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    private static function deleteFiles(WarehouseIdentityCard $card): void
    {
        if ($card->photo_path) {
            Storage::disk('local')->delete($card->photo_path);
        }
        if ($card->pdf_path) {
            Storage::disk('local')->delete($card->pdf_path);
        }
    }
}
