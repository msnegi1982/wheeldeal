<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\EnquiryController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/home', [CatalogController::class, 'home'])->name('home');
    Route::get('/filters', [CatalogController::class, 'filters'])->name('filters');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/{uuid}', [VehicleController::class, 'show'])->name('vehicles.show');

    Route::post('/requests', [RequestController::class, 'store'])
        ->middleware('throttle:public-forms')
        ->name('requests.store');
    Route::get('/requests/{customerRequest}', [RequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{customerRequest}/verify', [RequestController::class, 'verify'])
        ->middleware('throttle:otp-verify')
        ->name('requests.verify');
    Route::post('/requests/{customerRequest}/resend', [RequestController::class, 'resend'])
        ->middleware('throttle:otp-send')
        ->name('requests.resend');

    Route::post('/enquiries', [EnquiryController::class, 'store'])
        ->middleware('throttle:public-forms')
        ->name('enquiries.store');

    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:public-forms')
        ->name('contact.store');
});
