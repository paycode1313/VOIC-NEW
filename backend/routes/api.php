<?php

use App\Http\Controllers\Api\v1\AiRoleController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\ExamController;
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

Route::get('/', fn () => response()->json([
    'status' => 'online',
    'app' => 'VOIC API',
    'version' => 'v1',
]));

Route::prefix('v1')->group(function () {

    Route::get('/', fn () => response()->json([
        'status' => 'online',
        'message' => 'VOIC API v1 is active',
        'endpoints' => [
            'login' => '/api/v1/auth/login',
            'register' => '/api/v1/auth/register',
            'roles' => '/api/v1/roles',
            'modes' => '/api/v1/exam/modes',
        ],
    ]));

    // Public Authentication & Reference Endpoints
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('api.v1.auth.register');
    Route::get('/roles', [AiRoleController::class, 'index'])->name('api.v1.roles.index');
    Route::get('/exam/modes', [ExamController::class, 'modes'])->name('api.v1.exam.modes');
    Route::get('/exam/status', [ExamController::class, 'status'])->middleware('throttle:30,1')->name('api.v1.exam.status');

    // Protected Mobile Endpoints (Requires Sanctum Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {
        // User Profile & Logout
        Route::get('/auth/user', [AuthController::class, 'user'])->name('api.v1.auth.user');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // Practice history (all sessions)
        Route::get('/practice', [PracticeSessionController::class, 'index'])->name('api.v1.practice.index');
        Route::get('/practice/{practiceSession}', [PracticeSessionController::class, 'show'])->name('api.v1.practice.show');

        // Three-part mock exam (same engine as the web app)
        Route::post('/exam/{mode}/start', [ExamController::class, 'start'])->whereIn('mode', array_keys(config('exam.modes')))->middleware('throttle:10,1')->name('api.v1.exam.start');
        Route::post('/exam/{practiceSession}/answer', [ExamController::class, 'answer'])->whereNumber('practiceSession')->middleware('throttle:40,1')->name('api.v1.exam.answer');
        Route::post('/exam/{practiceSession}/finish', [ExamController::class, 'finish'])->whereNumber('practiceSession')->name('api.v1.exam.finish');
        Route::get('/exam/{practiceSession}', [ExamController::class, 'show'])->whereNumber('practiceSession')->name('api.v1.exam.show');
        Route::delete('/exam/{practiceSession}', [ExamController::class, 'destroy'])->whereNumber('practiceSession')->name('api.v1.exam.destroy');
        Route::get('/exam/{practiceSession}/backchannels', [ExamController::class, 'backchannels'])->whereNumber('practiceSession')->middleware('throttle:30,1')->name('api.v1.exam.backchannels');
        Route::get('/exam/{practiceSession}/improvements', [ExamController::class, 'improvements'])->whereNumber('practiceSession')->middleware('throttle:20,1')->name('api.v1.exam.improvements');
        Route::get('/exam/{practiceSession}/recordings/{message}', [ExamController::class, 'recording'])->whereNumber(['practiceSession', 'message'])->name('api.v1.exam.recording');
        Route::post('/exam/{practiceSession}/messages/{message}/rate', [ExamController::class, 'rate'])->whereNumber(['practiceSession', 'message'])->middleware('throttle:60,1')->name('api.v1.exam.rate');
    });

});
