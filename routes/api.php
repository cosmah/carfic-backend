<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\BlogInteractionController;

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


// Blog Post Routes
Route::get('/blog/categories', [BlogPostController::class, 'getCategories']);
Route::get('/blog/tags', [BlogPostController::class, 'getTags']);
Route::post('/blog/{id}/view', [BlogInteractionController::class, 'view']);
// List all blog posts (GET)
Route::get('/blog', [BlogPostController::class, 'index']);
Route::get('/blog/{id}', [BlogPostController::class, 'show']);
// View a specific blog post (GET)
Route::get('/blog/{id}', [BlogPostController::class, 'show']);



// Protected routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // FAQ Routes - Protected endpoints
    Route::post('/faqs/store', [FAQController::class, 'store']);
    Route::put('/faqs/update/{id}', [FAQController::class, 'update']);
    Route::delete('/faqs/delete/{id}', [FAQController::class, 'destroy']);



    // Blog Post Interactions
    Route::post('/blog/{id}/like', [BlogInteractionController::class, 'like']);
    Route::post('/blog/{id}/dislike', [BlogInteractionController::class, 'dislike']);
    // Comment Routes
    Route::post('/blog/{blogPostId}/comments', [CommentController::class, 'store']);
    Route::put('/blog/{blogPostId}/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/blog/{blogPostId}/comments/{id}', [CommentController::class, 'destroy']);
    Route::post('/blog/{blogPostId}/comments/{id}/like', [CommentController::class, 'like']);
    // Create a new blog post (POST)
    Route::post('/blog', [BlogPostController::class, 'store']);
    // Update a specific blog post (PUT/PATCH)
    Route::put('/blog/{id}', [BlogPostController::class, 'update']);
    Route::patch('/blog/{id}', [BlogPostController::class, 'update']);
    // Delete a specific blog post (DELETE)
    Route::delete('/blog/{id}', [BlogPostController::class, 'destroy']);

});
