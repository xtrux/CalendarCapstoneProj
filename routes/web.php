<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});
Route::get('/login', function () {
    return view('login');
});

Route::get('/register', function () {
    return view('register');
});
Route::get('/settings', function () {
    return view('settings');
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);


Route::post('/changeUsername', [SettingsController::class, 'changeUsername']);
Route::post('/changePassword', [SettingsController::class, 'changePassword']);
Route::post('/changeEmail',    [SettingsController::class, 'changeEmail']);
Route::post('/changeProfilePicture', [SettingsController::class, 'changeProfilePicture']);