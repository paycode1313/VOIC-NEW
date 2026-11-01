<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoSessionController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PracticeSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Public Digital Certificate Verification (Accessible via QR Code scan)
Route::get('/verify/{practiceSession}', [PracticeSessionController::class, 'verify'])
    ->name('practice.verify');

// Guest / Demo Mode for Innofest Exhibition Booth (No Registration Required)
Route::prefix('demo')->name('demo.')->group(function () {
    Route::get('/', [DemoSessionController::class, 'create'])->name('create');
    Route::post('/start', [DemoSessionController::class, 'start'])->name('start');
    Route::post('/{practiceSession}/message', [DemoSessionController::class, 'message'])->name('message');
    Route::post('/{practiceSession}/finish', [DemoSessionController::class, 'finish'])->name('finish');
    Route::get('/{practiceSession}', [DemoSessionController::class, 'show'])->name('show');
    Route::post('/reset', [DemoSessionController::class, 'reset'])->name('reset');
});

Route::middleware('auth')->group(function () {
    Route::get('/practice', [PracticeSessionController::class, 'create'])->name('practice.create');
    Route::get('/practice/history', [PracticeSessionController::class, 'index'])->name('practice.history');
    Route::post('/practice', [PracticeSessionController::class, 'store'])->name('practice.store');
    Route::post('/practice/start', [PracticeSessionController::class, 'start'])->name('practice.start');
    Route::post('/practice/{practiceSession}/message', [PracticeSessionController::class, 'message'])->name('practice.message');
    Route::post('/practice/{practiceSession}/finish', [PracticeSessionController::class, 'finish'])->name('practice.finish');
    Route::get('/practice/{practiceSession}', [PracticeSessionController::class, 'show'])->name('practice.show');
    Route::delete('/practice/{practiceSession}', [PracticeSessionController::class, 'destroy'])->name('practice.destroy');
    Route::get('/practice-export-csv', [PracticeSessionController::class, 'exportCsv'])->name('practice.export');

    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
