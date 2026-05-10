<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\SettingsController;
use App\Models\Exception as ExceptionModel;
use Illuminate\Support\Facades\Route;

// Homepage
Route::get('/', function () {
    $exceptions = auth()->check()
        ? ExceptionModel::where('user_id', auth()->id())->latest()->get()
        : collect();

    return view('welcome', compact('exceptions'));
});

Route::get('/login', fn () => redirect('/'))->name('login');

// Guest-only auth routes
Route::middleware('guest')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

// OAuth
Route::get('/auth/slack', [AuthController::class, 'redirectToSlack'])->name('auth.slack');
Route::get('/auth/slack/callback', [AuthController::class, 'handleSlackCallback'])->name('auth.slack.callback');

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');
    Route::get('/exceptions/{exception}', [ExceptionController::class, 'show'])->name('exceptions.show');
    Route::post('/exceptions/trigger', [ExceptionController::class, 'trigger'])->name('exceptions.trigger');
    Route::delete('/exceptions/{exception}', [ExceptionController::class, 'destroy'])->name('exceptions.destroy');
    Route::patch('/exceptions/{exception}/resolve', [ExceptionController::class, 'resolve'])->name('exceptions.resolve');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/password', [SettingsController::class, 'setPassword'])->name('settings.password');
    Route::get('/auth/slack/connect', [SettingsController::class, 'connectSlack'])->name('auth.slack.connect');
    Route::post('/settings/slack-token', [SettingsController::class, 'testSlackToken'])->name('settings.slack-token');
});
