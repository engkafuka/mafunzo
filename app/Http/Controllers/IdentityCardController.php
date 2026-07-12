<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\TrainingApplication;
use App\Models\WarehouseIdentityCard;
use App\Support\IdentityCardService;
use App\Support\PaginationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class IdentityCardController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::orderBy('name')->get();
        $statusFilter = $request->string('status_filter')->toString() ?: 'all';

        $query = TrainingApplication::with(['course', 'user', 'warehouseIdentityCard'])
            ->whereNotNull('registration_number')
            ->orderBy('registration_number');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($statusFilter === 'eligible') {
            $query->where('status', 'payment_completed')
                ->where('application_review_status', 'approved')
                ->whereNotNull('account_verified_at')
                ->whereNotNull('payment_verified_at')
                ->where('exam_passed', true)
                ->whereNotNull('exam_results_published_at')
                ->whereHas('user', fn ($q) => $q->whereNotNull('profile_photo_path'))
                ->whereDoesntHave('warehouseIdentityCard');
        } elseif ($statusFilter === 'draft') {
            $query->whereHas('warehouseIdentityCard', fn ($q) => $q->where('status', WarehouseIdentityCard::STATUS_DRAFT));
        } elseif ($statusFilter === 'published') {
            $query->whereHas('warehouseIdentityCard', fn ($q) => $q->where('status', WarehouseIdentityCard::STATUS_PUBLISHED));
        }

        $applications = $query->paginate(PaginationHelper::PER_PAGE)->withQueryString();

        $generatedCards = WarehouseIdentityCard::query()
            ->with(['trainingApplication.course', 'user', 'generator'])
            ->when($request->filled('course_id'), function ($query) use ($request) {
                $query->whereHas('trainingApplication', fn ($q) => $q->where('course_id', $request->integer('course_id')));
            })
            ->when($request->filled('card_status'), function ($query) use ($request) {
                $query->where('status', $request->string('card_status')->toString());
            })
            ->orderByDesc('generated_at')
            ->paginate(PaginationHelper::PER_PAGE, ['*'], 'cards_page')
            ->withQueryString();

        return view('application-management.identity-cards.index', compact('applications', 'generatedCards', 'courses', 'statusFilter'));
    }

    public function show(TrainingApplication $application): View
    {
        $application->load(['course', 'user', 'warehouseIdentityCard.publisher', 'warehouseIdentityCard.generator']);

        $timeline = AuditLog::query()
            ->where(function ($query) use ($application) {
                $query->where(function ($q) use ($application) {
                    $q->where('auditable_type', TrainingApplication::class)
                        ->where('auditable_id', $application->id);
                })->orWhere(function ($q) use ($application) {
                    if ($application->warehouseIdentityCard) {
                        $q->where('auditable_type', WarehouseIdentityCard::class)
                            ->where('auditable_id', $application->warehouseIdentityCard->id);
                    } else {
                        $q->whereRaw('0 = 1');
                    }
                });
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('application-management.identity-cards.show', compact('application', 'timeline'));
    }

    public function generate(TrainingApplication $application): RedirectResponse
    {
        try {
            IdentityCardService::generate($application, auth()->user());
        } catch (\RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('app-management.identity-cards.show', $application)
            ->with('status', __('Identity card draft generated.'));
    }

    public function publish(WarehouseIdentityCard $identityCard): RedirectResponse
    {
        try {
            IdentityCardService::publish($identityCard, auth()->user());
        } catch (\RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('app-management.identity-cards.show', $identityCard->training_application_id)
            ->with('status', __('Identity card published. The trainee can now download it.'));
    }

    public function revoke(WarehouseIdentityCard $identityCard): RedirectResponse
    {
        if (! auth()->user()->isAdminOrSuperAdmin()) {
            abort(403);
        }

        try {
            IdentityCardService::revoke($identityCard, auth()->user());
        } catch (\RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('app-management.identity-cards.show', $identityCard->training_application_id)
            ->with('status', __('Identity card revoked.'));
    }

    public function view(WarehouseIdentityCard $identityCard): Response|RedirectResponse
    {
        if (! in_array(auth()->user()->role, ['super_admin', 'admin', 'staff'], true)) {
            abort(403);
        }

        try {
            $identityCard->update(['pdf_path' => IdentityCardService::buildPdf($identityCard)]);
            $identityCard->refresh();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->with('error', __('Could not generate the identity card PDF. Please contact the system administrator.'));
        }

        $filename = 'wrrb-id-'.$identityCard->registration_number.'.pdf';

        return response()->file(Storage::disk('local')->path($identityCard->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(WarehouseIdentityCard $identityCard): Response
    {
        $user = auth()->user();
        $canDownload = in_array($user->role, ['super_admin', 'admin', 'staff'], true)
            || $identityCard->user_id === $user->id;

        if (! $canDownload || ! $identityCard->isPublished()) {
            abort(403);
        }

        if (! $identityCard->pdf_path || ! Storage::disk('local')->exists($identityCard->pdf_path)) {
            abort(404);
        }

        $filename = 'wrrb-id-'.$identityCard->registration_number.'.pdf';

        return response()->file(Storage::disk('local')->path($identityCard->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
