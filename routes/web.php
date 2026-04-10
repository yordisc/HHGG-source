<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\CertificateAdminController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CertificateTemplateController;
use App\Http\Controllers\Admin\CertificationAdminController;
use App\Http\Controllers\Admin\QuestionAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Api\CertificationApiController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateImageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QuizController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/search', [HomeController::class, 'search'])->name('search');

Route::get('/exam/{certType}/register', [QuizController::class, 'register'])->name('quiz.register');
Route::post('/exam/start', [QuizController::class, 'start'])
    ->name('quiz.start')
    ->middleware(['quiz.rate.limit', 'throttle:8,1']);
Route::post('/exam/eligibility-check', [QuizController::class, 'eligibilityCheck'])
    ->name('quiz.eligibility')
    ->middleware(['throttle:30,1']);
Route::get('/exam/{certType}', [QuizController::class, 'show'])->name('quiz.show');

Route::get('/result/{serial}', [CertificateController::class, 'result'])->name('result.show');
Route::get('/cert/{serial}', [CertificateController::class, 'show'])->name('cert.show');
Route::get('/cert/verify/{serial}/{token}', [CertificateController::class, 'verify'])->name('cert.verify');
Route::get('/cert/{serial}/pdf', [CertificateController::class, 'downloadPdf'])->name('cert.pdf');
Route::post('/cert/{serial}/image', [CertificateImageController::class, 'store'])
    ->middleware('admin.auth')
    ->name('cert.image.store');
Route::delete('/cert/{serial}/image', [CertificateImageController::class, 'delete'])
    ->middleware('admin.auth')
    ->name('cert.image.delete');

Route::get('/locale/{locale}', function (Request $request, string $locale): RedirectResponse {
    $supported = config('app.supported_locales', ['en']);

    if (in_array($locale, $supported, true)) {
        session(['locale' => $locale]);
    }

    // Use back() which safely handles the referer header
    return back(fallback: route('home'));
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/certificates', [CertificateAdminController::class, 'index'])->name('certificates.index');
        Route::get('/certificates/export-csv', [CertificateAdminController::class, 'exportCsv'])->name('certificates.export.csv');
        Route::get('/certificates/api', [CertificateAdminController::class, 'apiIndex'])->name('certificates.api.index');

        Route::resource('certifications', CertificationAdminController::class)->except(['show']);
        Route::post('/certifications/{certification}/duplicate', [CertificationAdminController::class, 'duplicate'])
            ->name('certifications.duplicate');
        Route::get('/certifications/{certification}/versions', [CertificationAdminController::class, 'showVersions'])
            ->name('certifications.versions');
        Route::post('/certifications/{certification}/versions/{version}/rollback', [CertificationAdminController::class, 'rollbackVersion'])
            ->name('certifications.rollback-version');
        Route::post('/certifications/reorder', [CertificationAdminController::class, 'reorder'])
            ->name('certifications.reorder');
        Route::get('/certifications-wizard/drafts', [CertificationAdminController::class, 'wizardDrafts'])
            ->name('certifications.wizard.drafts');
        Route::post('/certifications-wizard/autosave', [CertificationAdminController::class, 'wizardAutoSave'])
            ->name('certifications.wizard.autosave');
        Route::get('/certifications/wizard/{step?}', [CertificationAdminController::class, 'wizard'])
            ->whereNumber('step')
            ->name('certifications.wizard');
        Route::post('/certifications/wizard/{step?}', [CertificationAdminController::class, 'wizardStore'])
            ->whereNumber('step')
            ->name('certifications.wizard.store');
        Route::post('/certifications/wizard-reset', [CertificationAdminController::class, 'wizardReset'])
            ->name('certifications.wizard.reset');
        Route::get('/certifications/{certification}/test', [CertificationAdminController::class, 'test'])
            ->name('certifications.test');
        Route::post('/certifications/{certification}/test-questions', [CertificationAdminController::class, 'generateTestQuestions'])
            ->name('certifications.test-questions');
        Route::delete('/certifications/{certification}/test-questions', [CertificationAdminController::class, 'clearTestQuestions'])
            ->name('certifications.test-questions.clear');
        Route::patch('/certifications/{certification}/toggle', [CertificationAdminController::class, 'toggle'])
            ->name('certifications.toggle');
        Route::post('/certifications/{certification}/certificates/{certificate}/revoke', [CertificationAdminController::class, 'revokeCertificate'])
            ->name('certifications.certificates.revoke');
        Route::post('/certifications/{certification}/certificates/{certificate}/restore', [CertificationAdminController::class, 'restoreCertificate'])
            ->name('certifications.certificates.restore');
        Route::get('/api/check-slug', [CertificationAdminController::class, 'checkSlug'])
            ->name('api.check.slug');

        // API Endpoints for certification editing
        Route::prefix('api')->name('api.certifications.')->group(function () {
            Route::get('/certifications/{certification}/available-questions', 
                [CertificationApiController::class, 'availableQuestions'])
                ->name('available-questions');
            Route::get('/certifications/{certification}/active-attempts',
                [CertificationApiController::class, 'activeAttempts'])
                ->name('active-attempts');
            Route::get('/certifications/{certification}/versions/{versionId}/compare',
                [CertificationApiController::class, 'compareVersions'])
                ->name('versions.compare');
        });

        Route::prefix('certificates/templates')->name('certificates.templates.')->group(function () {
            Route::get('/', [CertificateTemplateController::class, 'index'])->name('index');
            Route::get('/create', [CertificateTemplateController::class, 'create'])->name('create');
            Route::post('/', [CertificateTemplateController::class, 'store'])->name('store');
            Route::get('/{template}/edit', [CertificateTemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}', [CertificateTemplateController::class, 'update'])->name('update');
            Route::delete('/{template}', [CertificateTemplateController::class, 'destroy'])->name('destroy');
            Route::get('/{template}/preview', [CertificateTemplateController::class, 'preview'])->name('preview');
        });

        Route::get('/certifications/{certification}/template', [CertificateTemplateController::class, 'certificationTemplates'])
            ->name('certificates.templates.certification');
        Route::post('/certifications/{certification}/template', [CertificateTemplateController::class, 'saveCertificationTemplate'])
            ->name('certificates.templates.certification.save');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserAdminController::class, 'index'])->name('index');
            Route::get('/create', [UserAdminController::class, 'create'])->name('create');
            Route::post('/', [UserAdminController::class, 'store'])->name('store');
            Route::get('/export-csv', [UserAdminController::class, 'exportCsv'])->name('export.csv');
            Route::get('/import-csv', [UserAdminController::class, 'importCsvForm'])->name('import.form');
            Route::post('/import-csv', [UserAdminController::class, 'importCsv'])->name('import.csv');
            Route::get('/{user}/edit', [UserAdminController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserAdminController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserAdminController::class, 'destroy'])->name('destroy');
        });

        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        Route::prefix('questions')->name('questions.')->group(function () {
            Route::get('/', [QuestionAdminController::class, 'index'])->name('index');
            Route::get('/builder', [QuestionAdminController::class, 'builder'])->name('builder');
            Route::get('/create', [QuestionAdminController::class, 'create'])->name('create');
            Route::post('/', [QuestionAdminController::class, 'store'])->name('store');
            Route::post('/validate-csv', [QuestionAdminController::class, 'validateCsv'])->name('validate.csv');
            Route::post('/confirm-csv', [QuestionAdminController::class, 'confirmCsvImport'])->name('confirm.csv');
            Route::post('/import-csv', [QuestionAdminController::class, 'importCsv'])->name('import.csv');
            Route::get('/export-csv', [QuestionAdminController::class, 'exportCsv'])->name('export.csv');
            Route::get('/template-csv', [QuestionAdminController::class, 'downloadTemplateCsv'])->name('template.csv');
            Route::get('/{question}/edit', [QuestionAdminController::class, 'edit'])->name('edit');
            Route::put('/{question}', [QuestionAdminController::class, 'update'])->name('update');
            Route::delete('/{question}', [QuestionAdminController::class, 'destroy'])->name('destroy');
            Route::post('/{question}/duplicate', [QuestionAdminController::class, 'duplicate'])->name('duplicate');
        });
    });
});

// Backward-compatible named API routes expected by some tests.
Route::middleware('admin.auth')->prefix('admin/api')->name('api.certifications.')->group(function () {
    Route::get('/certifications/{certification}/available-questions',
        [CertificationApiController::class, 'availableQuestions'])
        ->name('available-questions');
    Route::get('/certifications/{certification}/active-attempts',
        [CertificationApiController::class, 'activeAttempts'])
        ->name('active-attempts');
    Route::get('/certifications/{certification}/versions/{versionId}/compare',
        [CertificationApiController::class, 'compareVersions'])
        ->name('versions.compare');
});
