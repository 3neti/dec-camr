<?php

use App\Http\Controllers\Auth\LegacyAuthController;
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

require __DIR__.'/settings.php';
