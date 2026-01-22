<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\StudentImportController;
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

/**
 ***************************************************************************
 * V1 routes
 ***************************************************************************
 */
Route::name('v1')->prefix('v1')->group(function () {
    /**
     ***************************************************************************
     * Students
     ***************************************************************************
     */
    Route::name('students.')->prefix('students')->middleware('auth:sanctum')->group(function () {
        // Import
        Route::name('import.')->prefix('import')->group(function () {
            // Upload
            Route::post('/', [StudentImportController::class, 'upload'])->name('upload');
            // Check progress
            Route::get('{import}', [StudentImportController::class, 'checkImportStatus'])->name('checkImportStatus');
        });
    });

    /**
     ***************************************************************************
     * Contacts
     ***************************************************************************
     */
    Route::name('contacts.')->prefix('contacts')->middleware('auth:sanctum')->group(function () {
        // Create
        Route::post('/', [ContactController::class, 'store'])->name('store');
        // Show
        Route::get('{contact}', [ContactController::class, 'show'])->name('show');
        // Update
        Route::put('{contact}', [ContactController::class, 'update'])->name('update');
        // Delete
        Route::delete('{contact}', [ContactController::class, 'destroy'])->name('destroy');
    });
});
