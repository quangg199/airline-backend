<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AirportController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\BookingController;

/*
|--------------------------------------------------------------------------
| API Route Security Tiers
|--------------------------------------------------------------------------
|
| TIER 1 — PUBLIC      : No authentication required.
| TIER 2 — AUTH        : Authentication management endpoints (no token needed).
| TIER 3 — MEMBER      : Requires valid Sanctum token (auth:sanctum).
| TIER 4 — ADMIN       : Requires valid token + 'admin' role (auth:sanctum + role:admin).
|
| The CheckRole middleware (Protection Proxy) is always chained AFTER auth:sanctum.
| Ordering matters: sanctum confirms identity, CheckRole confirms authorization.
|
*/

// -------------------------------------------------------------------------
// TIER 1: PUBLIC ROUTES
// No authentication. Safe for unauthenticated browsing.
// -------------------------------------------------------------------------
Route::get('/airports', [AirportController::class, 'index']);
Route::get('/flights',  [FlightController::class,  'index']);
Route::get('/services', [ServiceController::class, 'index']);

// -------------------------------------------------------------------------
// TIER 2: AUTHENTICATION ROUTES
// Open endpoints for identity management (register, login).
// Logout and /me require a valid token — scoped separately inside.
// -------------------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    // These two require a valid Sanctum token
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// -------------------------------------------------------------------------
// TIER 3: MEMBER-PROTECTED ROUTES
// Requires: Valid Sanctum Bearer token.
// Any authenticated user (member or admin) can access these.
// -------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings',       [BookingController::class, 'index']);
    Route::post('/bookings',      [BookingController::class, 'store']);
    Route::get('/bookings/{id}',  [BookingController::class, 'show']);
});

// -------------------------------------------------------------------------
// TIER 4: ADMIN-PROTECTED ROUTES
// Requires: Valid Sanctum token AND the 'admin' role.
// The CheckRole('admin') Protection Proxy guards all routes in this group.
// Add admin-only endpoints here as the system grows.
// -------------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Flight management (admin only)
    Route::get('/flights',          [FlightController::class, 'index']);
    // Future admin endpoints will be added here:
    // Route::post('/flights',      [FlightController::class, 'store']);
    // Route::put('/flights/{id}',  [FlightController::class, 'update']);
    // Route::delete('/flights/{id}', [FlightController::class, 'destroy']);

    // Airport management (admin only)
    // Route::post('/airports', [AirportController::class, 'store']);
});
