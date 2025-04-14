<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// This will handle direct visits to the verification URL from emails
Route::get('verify-email/{id}/{hash}', function ($id, $hash) {
    return redirect('/api/email/verify/' . $id . '/' . $hash);
})->name('web.verification.verify');

// Fallback route
Route::fallback(function () {
    return response()->json(['message' => 'Route not found'], 404);
});

require __DIR__.'/auth.php';
