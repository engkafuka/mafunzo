<?php

use App\Http\Controllers\ApplicationManagementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamResultsController;
use App\Http\Controllers\IdentityCardController;
use App\Http\Controllers\IdentityCardVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\RegistrationPendingController;
use App\Http\Controllers\RegistrationResubmissionController;
use App\Http\Controllers\RegistrationVerificationController;
use App\Http\Controllers\TraineeIdentityCardController;
use App\Http\Controllers\TraineeProfileController;
use App\Http\Controllers\TrainingApplicationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TmxAuctionController;
use App\Http\Controllers\WrmsApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Attendance scan: public so trainee can scan QR and enter registration number
Route::get('/attendance/scan', [ApplicationManagementController::class, 'attendanceScanPage'])->name('app-management.attendance.scan-page');
Route::post('/attendance/scan', [ApplicationManagementController::class, 'attendanceScanSubmit'])->name('app-management.attendance.scan-submit');

// Public verification for warehouse identity cards
Route::get('/verify/id/{token}', [IdentityCardVerificationController::class, 'show'])->name('identity-cards.verify');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/registration/pending', RegistrationPendingController::class)->name('registration.pending');
    Route::get('/registration/resubmit', [RegistrationResubmissionController::class, 'edit'])->name('registration.resubmit');
    Route::put('/registration/resubmit', [RegistrationResubmissionController::class, 'update'])->name('registration.resubmit.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile-photos/{user}', [ProfilePhotoController::class, 'show'])->name('profile-photos.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Trainee profile and training (requires staff-approved registration)
    Route::middleware('registration.approved')->group(function () {
        Route::get('/trainee/profile', [TraineeProfileController::class, 'edit'])->name('trainee.profile.edit');
        Route::put('/trainee/profile', [TraineeProfileController::class, 'update'])->name('trainee.profile.update');
        Route::post('/trainee/profile', [TraineeProfileController::class, 'store'])->name('trainee.profile.store');
        Route::get('/trainee/profile/certificate/{educationBackground}', [TraineeProfileController::class, 'showCertificate'])->name('trainee.profile.certificate');

        Route::prefix('training')->name('training.')->group(function () {
            Route::get('/', [TrainingApplicationController::class, 'selectCourse'])->name('select-course');
            Route::get('/apply', [TrainingApplicationController::class, 'create'])->name('apply');
            Route::post('/apply', [TrainingApplicationController::class, 'store'])->name('apply.store');
            Route::get('/my-applications', [TrainingApplicationController::class, 'index'])->name('my-applications');
            Route::get('/payment/{application}', [TrainingApplicationController::class, 'payment'])->name('payment');
            Route::post('/payment/{application}/confirm', [TrainingApplicationController::class, 'confirmPayment'])->name('payment.confirm');
            Route::get('/confirmation/{application}', [TrainingApplicationController::class, 'confirmation'])->name('confirmation');
            Route::get('/exam-results', [TrainingApplicationController::class, 'examResults'])->name('exam-results');
            Route::get('/identity-cards', [TraineeIdentityCardController::class, 'index'])->name('identity-cards');
            Route::get('/identity-cards/{identityCard}/download', [IdentityCardController::class, 'download'])->name('identity-cards.download');
        });
    });

    Route::middleware('exam_management')->prefix('trainer')->name('trainer.')->group(function () {
        Route::get('/exam-results', [ExamResultsController::class, 'index'])->name('exam-results');
        Route::post('/exam-results', [ExamResultsController::class, 'store'])->name('exam-results.save');
    });

    // WRMS API data: super_admin only
    Route::middleware('super_admin')->prefix('wrms-api')->name('wrms-api.')->group(function () {
        Route::get('/', [WrmsApiController::class, 'index'])->name('index');
    });

    // TMX Auction data (Mode B): super_admin only
    Route::middleware('super_admin')->prefix('tmx-auction')->name('tmx-auction.')->group(function () {
        Route::get('/', [TmxAuctionController::class, 'index'])->name('index');
        Route::get('/export', [TmxAuctionController::class, 'export'])->name('export');
    });

    // User and course management: super_admin and admin only
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('courses', CourseController::class)->except(['show']);
        Route::post('courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
        Route::post('courses/{course}/unpublish', [CourseController::class, 'unpublish'])->name('courses.unpublish');
        Route::post('courses/{course}/new-session', [CourseController::class, 'newSession'])->name('courses.new-session');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        Route::post('application-management/exam-results/publish', [ExamResultsController::class, 'publish'])->name('app-management.exam-results.publish');
        Route::get('application-management/exam-results/export/pdf', [ExamResultsController::class, 'exportPdf'])->name('app-management.exam-results.export.pdf');
        Route::get('application-management/exam-results/export/excel', [ExamResultsController::class, 'exportExcel'])->name('app-management.exam-results.export.excel');
    });

    // Application management: super_admin, admin, staff
    Route::middleware('app_management')->prefix('application-management')->name('app-management.')->group(function () {
        Route::get('/', [ApplicationManagementController::class, 'index'])->name('index');
        Route::get('/registrations', [RegistrationVerificationController::class, 'index'])->name('registrations.index');
        Route::get('/registrations/{user}', [RegistrationVerificationController::class, 'show'])->name('registrations.show');
        Route::post('/registrations/{user}/approve', [RegistrationVerificationController::class, 'approve'])->name('registrations.approve');
        Route::post('/registrations/{user}/reject', [RegistrationVerificationController::class, 'reject'])->name('registrations.reject');
        Route::get('/registrations/training-certificate/{application}', [RegistrationVerificationController::class, 'trainingCertificate'])->name('registrations.training-certificate');
        Route::get('/applications', [ApplicationManagementController::class, 'applications'])->name('applications');
        Route::get('/applications/{application}', [ApplicationManagementController::class, 'applicationShow'])->name('applications.show');
        Route::post('/applications/{application}/review', [ApplicationManagementController::class, 'applicationReview'])->name('applications.review');
        Route::post('/applications/{application}/verify-account', [ApplicationManagementController::class, 'verifyAccount'])->name('applications.verify-account');
        Route::post('/applications/{application}/verify-payment', [ApplicationManagementController::class, 'verifyPayment'])->name('applications.verify-payment');
        Route::post('/applications/{application}/verify-payment-package', [ApplicationManagementController::class, 'verifyPaymentPackage'])->name('applications.verify-payment-package');
        Route::get('/attendance', [ApplicationManagementController::class, 'attendance'])->name('attendance');
        Route::post('/attendance', [ApplicationManagementController::class, 'attendanceCreate'])->name('attendance.store');
        Route::get('/attendance/{session}', [ApplicationManagementController::class, 'attendanceShow'])->name('attendance.show');
        Route::get('/attendance/scan/{token}', fn ($token) => redirect()->route('app-management.attendance.scan-page', ['token' => $token]))->name('attendance.scan');
        Route::get('/exam-results', [ExamResultsController::class, 'index'])->name('exam-results');
        Route::post('/exam-results', [ExamResultsController::class, 'store'])->name('exam-results.save');
        Route::get('/certificates', [ApplicationManagementController::class, 'certificates'])->name('certificates');
        Route::post('/certificates/signature', [ApplicationManagementController::class, 'uploadCertificateSignature'])->name('certificates.signature');
        Route::get('/certificates/{application}', [ApplicationManagementController::class, 'certificateShow'])->name('certificates.show');
        Route::post('/certificates/{application}/issue', [ApplicationManagementController::class, 'certificateIssue'])->name('certificates.issue');
        Route::get('/identity-cards', [IdentityCardController::class, 'index'])->name('identity-cards.index');
        Route::get('/identity-cards/applications/{application}', [IdentityCardController::class, 'show'])->name('identity-cards.show');
        Route::post('/identity-cards/applications/{application}/generate', [IdentityCardController::class, 'generate'])->name('identity-cards.generate');
        Route::post('/identity-cards/{identityCard}/publish', [IdentityCardController::class, 'publish'])->name('identity-cards.publish');
        Route::post('/identity-cards/{identityCard}/revoke', [IdentityCardController::class, 'revoke'])->name('identity-cards.revoke');
        Route::get('/identity-cards/{identityCard}/view', [IdentityCardController::class, 'view'])->name('identity-cards.view');
        Route::get('/identity-cards/{identityCard}/download', [IdentityCardController::class, 'download'])->name('identity-cards.download');
    });

    // Trainee profile routes moved to registration.approved group above
});

require __DIR__.'/auth.php';
