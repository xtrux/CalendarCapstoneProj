<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});
Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/register', function () {
    return view('register');
});
Route::get('/settings', function () {
    return view('settings');
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);
use App\Http\Controllers\PasswordResetController;

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::get('/unlock/{user}', [AuthController::class, 'unlock'])->middleware('signed')->name('account.unlock');
Route::middleware('auth')->group(function () {
    Route::get('/settings', function () {
        return view('settings');
    });
    // email verification stuff done by claude
    Route::get('/email/verify', function () {
        return view('verify-email');
    })->middleware('throttle:6,1')->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect('/')->with('success', 'Email verified!');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::post('/changeUsername', [SettingsController::class, 'changeUsername']);
    Route::post('/changePassword', [SettingsController::class, 'changePassword']);
    Route::post('/changeEmail', [SettingsController::class, 'changeEmail']);
    Route::post('/changeProfilePicture', [SettingsController::class, 'changeProfilePicture']);
    Route::post('/deleteUser', [SettingsController::class, 'deleteUser']);

    Route::get('/group/{group}', [GroupController::class, 'show']);
    Route::post('/group/create', [GroupController::class, 'createGroup']);
    Route::post('/group/join', [GroupController::class, 'joinGroup']);

    Route::post('/group/leave', [GroupController::class, 'leaveGroup']);
    Route::post('/group/delete', [GroupController::class, 'deleteGroup']);
    Route::post('/group/regenerate-invite', [GroupController::class, 'regenerateInvite']);
    Route::post('/group/remove-user', [GroupController::class, 'removeUser']);

    Route::get('group/{group}/calendar', [CalendarController::class, 'display']);
});
