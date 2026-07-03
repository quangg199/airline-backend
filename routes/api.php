<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AirportController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\Admin\FlightAdminController;
use App\Http\Controllers\Api\Admin\BookingAdminController;
use App\Http\Controllers\Api\Admin\AirportAdminController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\Admin\ProfileController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\UserProfileController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/airports', [AirportController::class, 'index']);
Route::get('/flights', [FlightController::class, 'index']);
Route::get('/flights/{id}/seats', [FlightController::class, 'seats']);
Route::get('/services', [ServiceController::class, 'index']);
Route::post('/check-in', [CheckInController::class, 'processCheckIn']);
Route::get('/check-in', [CheckInController::class, 'query']);

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| MEMBER ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // PROFILE
    Route::put('/profile', [UserProfileController::class, 'updateProfile']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);

    Route::post('/bookings/lock-seat', [BookingController::class, 'lockSeat']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/pay', [PaymentController::class, 'pay']);
    
    // API cho chức năng Đổi chuyến bay
    Route::post('/bookings/{id}/reschedule', [BookingController::class, 'reschedule']);
    Route::post('/bookings/{id}/pay-reschedule', [PaymentController::class, 'payReschedule']);
    
    // API cho chức năng Hủy vé
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index']);

        // PROFILE (FIX THIẾU ROUTE)
        Route::get('/profile', [ProfileController::class, 'show']);

        // FLIGHTS
        Route::get('/flights', [FlightAdminController::class, 'index']);
        Route::post('/flights', [FlightAdminController::class, 'store']);
        Route::put('/flights/{id}', [FlightAdminController::class, 'update']);
        Route::delete('/flights/{id}', [FlightAdminController::class, 'destroy']);

        //AirPorts
        Route::get('/airports', [AirportAdminController::class, 'index']);
        Route::post('/airports', [AirportAdminController::class, 'store']);
        Route::put('/airports/{id}', [AirportAdminController::class, 'update']);
        Route::delete('/airports/{id}', [AirportAdminController::class, 'destroy']);

        // BOOKINGS
        Route::get('/bookings', [BookingAdminController::class, 'index']);
        Route::get('/bookings/{id}', [BookingAdminController::class, 'show']);
        Route::post('/bookings', [BookingAdminController::class, 'store']);
        Route::put('/bookings/{id}', [BookingAdminController::class, 'update']);
        Route::delete('/bookings/{id}', [BookingAdminController::class, 'destroy']);

        // USERS
        Route::get('/users', [UserAdminController::class, 'index']);
        Route::get('/users/{id}', [UserAdminController::class, 'show']);
        Route::post('/users', [UserAdminController::class, 'store']);
        Route::put('/users/{id}', [UserAdminController::class, 'update']);
        Route::delete('/users/{id}', [UserAdminController::class, 'destroy']);
    });