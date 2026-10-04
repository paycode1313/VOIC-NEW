<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PracticeSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/practice', [PracticeSessionController::class, 'create'])->name('practice.create');
    Route::post('/practice', [PracticeSessionController::class, 'store'])->name('practice.store');
    Route::post('/practice/start', [PracticeSessionController::class, 'start'])->name('practice.start');
    Route::post('/practice/{practiceSession}/message', [PracticeSessionController::class, 'message'])->name('practice.message');
    Route::post('/practice/{practiceSession}/finish', [PracticeSessionController::class, 'finish'])->name('practice.finish');
    Route::get('/practice/{practiceSession}', [PracticeSessionController::class, 'show'])->name('practice.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
