<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Notification Microservice
|--------------------------------------------------------------------------
|
| API Version: v1
| Base URL: /api/v1
|
| Authentication: JWT Bearer Token (mock)
| Rate Limiting: 60 requests/minute per IP
|
*/

// ─────────────────────────────────────────────
// Public Routes (No Authentication Required)
// ─────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Mock JWT token generation
    Route::post('/auth/token', [AuthController::class, 'generateToken']);

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Notification Microservice is running.',
            'version' => 'v1',
            'timestamp' => now()->toISOString(),
        ]);
    });
});

// ─────────────────────────────────────────────
// Protected Routes (JWT Authentication Required)
// ─────────────────────────────────────────────
Route::prefix('v1')
    ->middleware([JwtAuthMiddleware::class, 'throttle:60,1'])
    ->group(function () {

        // Notification endpoints
        Route::post('/notifications/send', [NotificationController::class, 'send']);
        Route::get('/notifications/{id}/status', [NotificationController::class, 'status']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/retry', [NotificationController::class, 'retry']);

        // Analytics endpoints
        Route::get('/analytics/training-data', [AnalyticsController::class, 'trainingData']);

        // Dashboard
        Route::get('/dashboard/stats', [AnalyticsController::class, 'dashboardStats']);
    });
