<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\FAQController; // Note the corrected namespace with Api prefix

// Contact and Appointment routes
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/appointment', [AppointmentController::class, 'store']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Email verification routes
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->name('verification.verify');

Route::post('email/resend', [VerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

// Password reset routes
Route::post('password/email', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('password/reset', [PasswordResetController::class, 'reset']);

// FAQ Routes - Public endpoints
Route::get('/faqs', [FAQController::class, 'index']);
Route::get('/faqs/{id}', [FAQController::class, 'show']);
Route::get('/faqs/category/{category}', [FAQController::class, 'getByCategory']);


// Protected routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // FAQ Routes - Protected endpoints
        Route::post('/faqs/store', [FAQController::class, 'store']);
        Route::put('/faqs/update/{id}', [FAQController::class, 'update']);
        Route::delete('/faqs/delete/{id}', [FAQController::class, 'destroy']);

});
