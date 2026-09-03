<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

// Ruta para Aurora Leads
Route::prefix('v1')->group(function () {
    Route::post('/leads', [LeadController::class, 'receive']);
});

Route::post('/contact-messages', [ContactMessageController::class, 'store']);

Route::get('/services', [ServiceController::class, 'index']);

Route::post('/bookings', [BookingController::class, 'store']);
