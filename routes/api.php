<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/**
 ***************************************************************************
 * Authentication
 ***************************************************************************
 */
Route::name('auth.')->prefix('auth')->group(function () {
    // Login
    Route::post('login', LoginController::class)->name('login');
});
