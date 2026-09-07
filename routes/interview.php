<?php

use App\Http\Controllers\Interview\CompanyController;
use App\Http\Controllers\Interview\DashboardController;
use App\Http\Controllers\Interview\QuestionSetController;
use App\Http\Controllers\Interview\ReportController;
use App\Http\Controllers\Interview\ReviewController;
use App\Http\Controllers\Interview\ScoringController;
use App\Http\Controllers\Interview\SessionController;
use App\Http\Controllers\Interview\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'interview.access'])->prefix('interviews')->name('interview.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Static session paths must be registered BEFORE sessions/{session}
    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');

    Route::middleware('interview.access:admin,chair,approver,viewer')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('reports/company-register', [ReportController::class, 'companyRegister'])->name('reports.company-register');
        Route::get('reports/company-register/export/csv', [ReportController::class, 'companyRegisterCsv'])->name('reports.company-register.export.csv');
        Route::get('reports/company-register/export/pdf', [ReportController::class, 'companyRegisterPdf'])->name('reports.company-register.export.pdf');

        Route::get('reports/panel-attendance', [ReportController::class, 'panelAttendance'])->name('reports.panel-attendance');
        Route::get('reports/panel-attendance/export/csv', [ReportController::class, 'panelAttendanceCsv'])->name('reports.panel-attendance.export.csv');
        Route::get('reports/panel-attendance/export/pdf', [ReportController::class, 'panelAttendancePdf'])->name('reports.panel-attendance.export.pdf');

        Route::get('reports/audit-extract', [ReportController::class, 'auditExtract'])->name('reports.audit-extract');
        Route::get('reports/audit-extract/export/csv', [ReportController::class, 'auditExtractCsv'])->name('reports.audit-extract.export.csv');
        Route::get('reports/audit-extract/export/pdf', [ReportController::class, 'auditExtractPdf'])->name('reports.audit-extract.export.pdf');

        Route::get('reports/pass-rate', [ReportController::class, 'passRate'])->name('reports.pass-rate');
        Route::get('reports/pass-rate/export/csv', [ReportController::class, 'passRateCsv'])->name('reports.pass-rate.export.csv');
        Route::get('reports/pass-rate/export/pdf', [ReportController::class, 'passRatePdf'])->name('reports.pass-rate.export.pdf');
    });

    Route::middleware('interview.access:admin')->group(function () {
        Route::resource('companies', CompanyController::class)->except(['show', 'destroy']);

        Route::resource('question-sets', QuestionSetController::class)->except(['destroy']);
        Route::post('question-sets/{question_set}/questions', [QuestionSetController::class, 'storeQuestion'])->name('question-sets.questions.store');
        Route::put('question-sets/{question_set}/questions/{question}', [QuestionSetController::class, 'updateQuestion'])->name('question-sets.questions.update');

        Route::get('sessions/create', [SessionController::class, 'create'])->name('sessions.create');
        Route::post('sessions', [SessionController::class, 'store'])->name('sessions.store');

        Route::get('user-roles', [UserRoleController::class, 'index'])->name('user-roles.index');
        Route::get('user-roles/create', [UserRoleController::class, 'create'])->name('user-roles.create');
        Route::post('user-roles/users', [UserRoleController::class, 'storeUser'])->name('user-roles.store-user');
        Route::post('user-roles', [UserRoleController::class, 'store'])->name('user-roles.store');
        Route::put('user-roles/{user}', [UserRoleController::class, 'update'])->name('user-roles.update');
        Route::delete('user-roles/{user}/{role}', [UserRoleController::class, 'destroy'])->name('user-roles.destroy');
    });

    Route::get('sessions/{session}', [SessionController::class, 'show'])
        ->whereNumber('session')
        ->name('sessions.show');

    Route::middleware('interview.access:admin')->group(function () {
        Route::get('sessions/{session}/edit', [SessionController::class, 'edit'])
            ->whereNumber('session')
            ->name('sessions.edit');
        Route::put('sessions/{session}', [SessionController::class, 'update'])
            ->whereNumber('session')
            ->name('sessions.update');
        Route::post('sessions/{session}/open-scoring', [SessionController::class, 'openScoring'])
            ->whereNumber('session')
            ->name('sessions.open-scoring');
    });

    Route::get('sessions/{session}/score', [ScoringController::class, 'edit'])
        ->whereNumber('session')
        ->name('scoring.edit');
    Route::put('sessions/{session}/score', [ScoringController::class, 'update'])
        ->whereNumber('session')
        ->name('scoring.update');

    Route::get('sessions/{session}/review', [ReviewController::class, 'show'])
        ->whereNumber('session')
        ->name('review.show');
    Route::post('sessions/{session}/review/confirm', [ReviewController::class, 'confirm'])
        ->whereNumber('session')
        ->name('review.confirm');
    Route::post('sessions/{session}/review/approve', [ReviewController::class, 'approve'])
        ->whereNumber('session')
        ->name('review.approve');
    Route::get('sessions/{session}/export/pdf', [ReviewController::class, 'exportPdf'])
        ->whereNumber('session')
        ->name('review.export.pdf');
});
