<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\EventCategoryController;
use App\Http\Controllers\API\AdminController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Event categories
    Route::apiResource('categories', EventCategoryController::class);

    // Events
    Route::apiResource('events', EventController::class);
    Route::post('/events/{event}/register', [EventController::class, 'register']);
    Route::post('/events/{event}/cancel-registration', [EventController::class, 'cancelRegistration']);
    Route::get('/events/{event}/participants', [EventController::class, 'participants']);

    // Admin-only routes (with both auth and admin middleware)
    Route::middleware([AdminMiddleware::class])->prefix('admin')->group(function () {
        Route::controller(AdminController::class)->group(function () {
            // User Management
            Route::get('/users', 'users');
            Route::post('/users', 'createUser');
            Route::put('/users/{user}', 'updateUser');
            Route::delete('/users/{user}', 'deleteUser');
            Route::post('/users/{user}/toggle-active', 'toggleUserActive');

            // Event Management
            Route::post('/events/{event}/force-register', 'forceRegisterUser');
            Route::delete('/events/{event}/remove-participant', 'removeParticipant');

            // Statistics
            Route::get('/statistics', 'statistics');
        });
    });
});
