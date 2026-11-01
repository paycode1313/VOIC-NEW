<?php

use App\Http\Controllers\Api\v1\AiRoleController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\PracticeSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile & External REST API Routes (v1)
|--------------------------------------------------------------------------
|
| Universal API endpoints for VOIC mobile clients (Flutter / React Native).
| Powered by Laravel Sanctum Bearer Token Authentication.
|
*/

Route::prefix('v1')->group(function () {

    // Public Authentication & Reference Endpoints
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
    Route::get('/roles', [AiRoleController::class, 'index'])->name('api.v1.roles.index');

    // Protected Mobile Endpoints (Requires Sanctum Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {
        // User Profile & Logout
        Route::get('/auth/user', [AuthController::class, 'user'])->name('api.v1.auth.user');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // AI Roleplay Practice Sessions
        Route::get('/practice', [PracticeSessionController::class, 'index'])->name('api.v1.practice.index');
        Route::post('/practice/start', [PracticeSessionController::class, 'start'])->name('api.v1.practice.start');
        Route::post('/practice/{practiceSession}/message', [PracticeSessionController::class, 'message'])->name('api.v1.practice.message');
        Route::post('/practice/{practiceSession}/finish', [PracticeSessionController::class, 'finish'])->name('api.v1.practice.finish');
        Route::get('/practice/{practiceSession}', [PracticeSessionController::class, 'show'])->name('api.v1.practice.show');
    });

});
