<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\LineAuthService;

Route::middleware('guest')->group(function () {

    Route::get('/auth/redirect', function() {
        return Socialite::driver('google')->redirect();
    })->name('google.redirect');

    Route::get('/auth/line', function() {
        return LineAuthService::initiate()->redirect();
    })->name('line.redirect');

    Route::get('/auth/google/callback', function() {
        $googleUser = Socialite::driver('google')->user();
        $user = User::where('email', $googleUser->getEmail())->first();
        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'email_verified_at' => now(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        }

        Auth::login($user);
        if(!$user->avatar) {
            $user->update(['avatar' => $googleUser->getAvatar()]);
        }

        return redirect()->to('/dashboard');
    })->name('google.callback');

    Route::get('/auth/line/callback', function() {
        $lineUser = LineAuthService::initiate()->user();

        $user = User::where('email', $lineUser["email"])->first();

        if (!$user) {
            $user = User::create([
                'name' => $lineUser["name"],
                'email' => $lineUser["email"],
                'email_verified_at' => now(),
                'avatar' => $lineUser["picture"],
            ]);
        }
        Auth::login($user);
        if(!$user->avatar) {
            $user->update(['avatar' => $lineUser->picture]);
        }

        return redirect()->to('/dashboard');
        
    })->name('line.callback');

    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {

    

    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
