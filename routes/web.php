<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExceptionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [ExceptionController::class, 'showView'])->name('exceptions.index');

Route::middleware('guest')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::get('/auth/slack', [AuthController::class, 'redirectToSlack'])->name('auth.slack');
Route::get('/auth/slack/callback', [AuthController::class, 'handleSlackCallback'])->name('auth.slack.callback');

Route::middleware('auth')->group(function (): void {
    Route::post('/exceptions/trigger', [ExceptionController::class, 'trigger'])->name('exceptions.trigger');
    Route::get('/exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
