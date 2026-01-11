<?php

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('register', 'pages.auth.register')
        ->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

// Two-Factor Authentication - Challenge (for users mid-login)
Route::get('two-factor-challenge', [TwoFactorController::class, 'showChallenge'])
    ->name('two-factor.challenge');
Route::post('two-factor-challenge', [TwoFactorController::class, 'verify'])
    ->name('two-factor.verify');

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // Two-Factor Authentication - Setup and Management
    Route::get('two-factor', [TwoFactorController::class, 'showSetup'])
        ->name('two-factor.setup');
    Route::post('two-factor', [TwoFactorController::class, 'enable'])
        ->name('two-factor.enable');
    Route::delete('two-factor', [TwoFactorController::class, 'disable'])
        ->name('two-factor.disable');
    Route::get('two-factor/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])
        ->name('two-factor.recovery-codes');
    Route::post('two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.regenerate-codes');
});
