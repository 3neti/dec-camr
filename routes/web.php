<?php

use App\Http\Controllers\Auth\LegacyAuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DivisionController;
use App\Http\Middleware\EnsureLegacyAuthenticated;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Http\Controllers\NewPasswordController;

Route::get('/', [LegacyAuthController::class, 'loginPage'])->name('home');

Route::post('/login-user', [LegacyAuthController::class, 'loginUser'])->name('login-user');
Route::get('/logout', [LegacyAuthController::class, 'logout'])->name('legacy-logout');
Route::get('/passwordreset', [LegacyAuthController::class, 'passwordResetPage'])->name('passwordreset');
Route::post('/reset-password', [LegacyAuthController::class, 'requestTemporaryPassword'])->name('sendTemporaryPasswordtoEmail');
Route::post('/password-update', [NewPasswordController::class, 'store'])
    ->middleware('guest:'.config('fortify.guard'))
    ->name('password.update');

Route::get('/site', function () {
    return Inertia::render('Dashboard');
})->middleware([EnsureLegacyAuthenticated::class])->name('site');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware([EnsureLegacyAuthenticated::class])->group(function () {
    Route::get('/company', [CompanyController::class, 'company'])->name('company');
    Route::post('/company_list', [CompanyController::class, 'companyList'])->name('CompanyList');
    Route::post('/create_company_post', [CompanyController::class, 'createCompanyPost'])->name('create_company_post');
    Route::post('/company_info', [CompanyController::class, 'companyInfo'])->name('company_info');
    Route::post('/update_company_post', [CompanyController::class, 'updateCompanyPost'])->name('update_company_post');
    Route::post('/delete_company_confirmed', [CompanyController::class, 'deleteCompanyConfirmed'])->name('delete_company_confirmed');

    Route::get('/division', [DivisionController::class, 'division'])->name('division');
    Route::post('/division_list', [DivisionController::class, 'divisionList'])->name('DivisionList');
    Route::post('/create_division_post', [DivisionController::class, 'createDivisionPost'])->name('create_division_post');
    Route::post('/division_info', [DivisionController::class, 'divisionInfo'])->name('division_info');
    Route::post('/update_division_post', [DivisionController::class, 'updateDivisionPost'])->name('update_division_post');
    Route::post('/delete_division_confirmed', [DivisionController::class, 'deleteDivisionConfirmed'])->name('delete_division_confirmed');
});

require __DIR__.'/settings.php';
