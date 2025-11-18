<?php

use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/dashboard', fn() => redirect('/reviews'))->name('dashboard');
    Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
});


