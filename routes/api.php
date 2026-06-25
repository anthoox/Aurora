<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ContactMessageController;


// Ruta para Aurora Leads
Route::prefix('v1')->group(function () {
    Route::post('/leads', [LeadController::class, 'receive']);
});


Route::post('/contact-messages', [ContactMessageController::class, 'store']);