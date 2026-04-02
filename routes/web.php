<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\QuestionAdminController;
use App\Http\Controllers\CertificateController;
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
Route::get('/cert/{serial}/pdf', [CertificateController::class, 'downloadPdf'])->name('cert.pdf');

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
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::prefix('questions')->name('questions.')->group(function () {
            Route::get('/', [QuestionAdminController::class, 'index'])->name('index');
            Route::get('/create', [QuestionAdminController::class, 'create'])->name('create');
            Route::post('/', [QuestionAdminController::class, 'store'])->name('store');
            Route::post('/import-csv', [QuestionAdminController::class, 'importCsv'])->name('import.csv');
            Route::get('/export-csv', [QuestionAdminController::class, 'exportCsv'])->name('export.csv');
            Route::get('/template-csv', [QuestionAdminController::class, 'downloadTemplateCsv'])->name('template.csv');
            Route::get('/{question}/edit', [QuestionAdminController::class, 'edit'])->name('edit');
            Route::put('/{question}', [QuestionAdminController::class, 'update'])->name('update');
            Route::delete('/{question}', [QuestionAdminController::class, 'destroy'])->name('destroy');
        });
    });
});
