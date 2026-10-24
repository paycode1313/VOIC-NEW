<?php

use App\Http\Controllers\Mobile\Api\AuthController;
use App\Http\Controllers\Mobile\Api\PracticeSessionController;
use App\Http\Controllers\Mobile\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function () {
    // Public endpoints
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');

    // Protected endpoints
    Route::middleware('auth:sanctum')->group(function () {
        // Auth endpoints
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');

        // User endpoints
        Route::prefix('user')->group(function () {
            Route::get('/profile', [UserController::class, 'profile'])->name('api.user.profile');
            Route::patch('/profile', [UserController::class, 'update'])->name('api.user.update');
            Route::get('/stats', [UserController::class, 'stats'])->name('api.user.stats');
        });

        // Practice Session endpoints
        Route::prefix('practice-sessions')->group(function () {
            Route::get('/', [PracticeSessionController::class, 'index'])->name('api.practice.index');
            Route::post('/', [PracticeSessionController::class, 'store'])->name('api.practice.store');
            Route::get('/{practiceSession}', [PracticeSessionController::class, 'show'])->name('api.practice.show');
            Route::post('/{practiceSession}/message', [PracticeSessionController::class, 'message'])->name('api.practice.message');
            Route::post('/{practiceSession}/finish', [PracticeSessionController::class, 'finish'])->name('api.practice.finish');
            Route::get('/export/csv', [PracticeSessionController::class, 'export'])->name('api.practice.export');
        });
    });
});
