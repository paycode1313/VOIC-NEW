<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DemoSessionController;
use App\Http\Controllers\Web\ExamController;
use App\Http\Controllers\Web\OnboardingController;
use App\Http\Controllers\Web\PracticeSessionController;
use App\Http\Controllers\Web\ProfileController;
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

// Three-part mock exam (interview, thesis defense, startup pitch). Open to guests for the booth.
Route::prefix('latihan')->name('exam.')->controller(ExamController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/status-ai', 'status')->middleware('throttle:30,1')->name('status');
    Route::get('/sesi/{practiceSession}', 'room')->name('room');
    Route::post('/sesi/{practiceSession}/jawab', 'answer')->middleware('throttle:40,1')->name('answer');
    Route::post('/sesi/{practiceSession}/jawab-stream', 'answerStream')->middleware('throttle:40,1')->name('answer.stream');
    Route::get('/audio/{file}', 'audio')->middleware('throttle:240,1')->name('audio');
    Route::post('/sesi/{practiceSession}/selesai', 'finish')->name('finish');
    Route::get('/sesi/{practiceSession}/hasil', 'result')->name('result');
    Route::post('/sesi/{practiceSession}/pesan/{message}/nilai', 'rate')->scopeBindings()->middleware('throttle:60,1')->name('rate');
    Route::get('/{mode}', 'setup')->whereIn('mode', array_keys(config('exam.modes')))->name('setup');
    Route::post('/{mode}/mulai', 'start')->whereIn('mode', array_keys(config('exam.modes')))->middleware('throttle:10,1')->name('start');
});

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
