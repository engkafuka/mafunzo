<?php

use App\Http\Controllers\ApplicationManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
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

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Trainee: complete profile (education background + certificate)
    Route::get('/trainee/profile', [TraineeProfileController::class, 'edit'])->name('trainee.profile.edit');
    Route::post('/trainee/profile', [TraineeProfileController::class, 'store'])->name('trainee.profile.store');
    Route::get('/trainee/profile/certificate/{educationBackground}', [TraineeProfileController::class, 'showCertificate'])->name('trainee.profile.certificate');

    // WRMS API data: super_admin only
    Route::middleware('super_admin')->prefix('wrms-api')->name('wrms-api.')->group(function () {
        Route::get('/', [WrmsApiController::class, 'index'])->name('index');
    });

    // TMX Auction data (Mode B): super_admin only
    Route::middleware('super_admin')->prefix('tmx-auction')->name('tmx-auction.')->group(function () {
        Route::get('/', [TmxAuctionController::class, 'index'])->name('index');
        Route::get('/export', [TmxAuctionController::class, 'export'])->name('export');
    });

    // User management: super_admin and admin only
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    // Application management: super_admin, admin, staff
    Route::middleware('app_management')->prefix('application-management')->name('app-management.')->group(function () {
        Route::get('/', [ApplicationManagementController::class, 'index'])->name('index');
        Route::get('/applications', [ApplicationManagementController::class, 'applications'])->name('applications');
        Route::get('/applications/{application}', [ApplicationManagementController::class, 'applicationShow'])->name('applications.show');
        Route::post('/applications/{application}/review', [ApplicationManagementController::class, 'applicationReview'])->name('applications.review');
        Route::post('/applications/{application}/verify-account', [ApplicationManagementController::class, 'verifyAccount'])->name('applications.verify-account');
        Route::post('/applications/{application}/verify-payment', [ApplicationManagementController::class, 'verifyPayment'])->name('applications.verify-payment');
        Route::get('/attendance', [ApplicationManagementController::class, 'attendance'])->name('attendance');
        Route::post('/attendance', [ApplicationManagementController::class, 'attendanceCreate'])->name('attendance.store');
        Route::get('/attendance/{session}', [ApplicationManagementController::class, 'attendanceShow'])->name('attendance.show');
        Route::get('/attendance/scan/{token}', fn ($token) => redirect()->route('app-management.attendance.scan-page', ['token' => $token]))->name('attendance.scan');
        Route::get('/exam-results', [ApplicationManagementController::class, 'examResults'])->name('exam-results');
        Route::post('/exam-results', [ApplicationManagementController::class, 'examResultsSave'])->name('exam-results.save');
        Route::get('/certificates', [ApplicationManagementController::class, 'certificates'])->name('certificates');
        Route::get('/certificates/{application}', [ApplicationManagementController::class, 'certificateShow'])->name('certificates.show');
        Route::post('/certificates/{application}/issue', [ApplicationManagementController::class, 'certificateIssue'])->name('certificates.issue');
    });

    // Trainee: apply for training
    Route::prefix('training')->name('training.')->group(function () {
        Route::get('/', [TrainingApplicationController::class, 'selectCourse'])->name('select-course');
        Route::get('/apply', [TrainingApplicationController::class, 'create'])->name('apply');
        Route::post('/apply', [TrainingApplicationController::class, 'store'])->name('apply.store');
        Route::get('/my-applications', [TrainingApplicationController::class, 'index'])->name('my-applications');
        Route::get('/payment/{application}', [TrainingApplicationController::class, 'payment'])->name('payment');
        Route::post('/payment/{application}/confirm', [TrainingApplicationController::class, 'confirmPayment'])->name('payment.confirm');
        Route::get('/confirmation/{application}', [TrainingApplicationController::class, 'confirmation'])->name('confirmation');
    });
});

require __DIR__.'/auth.php';
