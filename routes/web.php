<?php

use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\EnquiryController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\RobotsController;
use App\Http\Controllers\Web\SellBuyController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\StaticPageController;
use App\Http\Controllers\Web\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/cars', [VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/cars/brand/{brand}', [VehicleController::class, 'brand'])->name('brands.show');
Route::get('/cars/{category}/{brand}', [VehicleController::class, 'categoryBrand'])->name('catalog.category-brand');
Route::get('/cars/{category}', [VehicleController::class, 'category'])->name('categories.show');
Route::get('/cars/{brand}/{model}/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

Route::get('/sell-vehicle', [SellBuyController::class, 'create'])
    ->defaults('type', 'sell')
    ->name('sell.create');
Route::post('/sell-vehicle', [SellBuyController::class, 'store'])
    ->defaults('type', 'sell')
    ->middleware('throttle:public-forms')
    ->name('sell.store');

Route::get('/buy-vehicle', [SellBuyController::class, 'create'])
    ->defaults('type', 'buy')
    ->name('buy.create');
Route::post('/buy-vehicle', [SellBuyController::class, 'store'])
    ->defaults('type', 'buy')
    ->middleware('throttle:public-forms')
    ->name('buy.store');

Route::get('/verify-otp/{customerRequest}', [SellBuyController::class, 'verify'])->name('requests.verify');
Route::post('/verify-otp/{customerRequest}', [SellBuyController::class, 'verifySubmit'])
    ->middleware('throttle:otp-verify')
    ->name('requests.verify.submit');
Route::post('/verify-otp/{customerRequest}/resend', [SellBuyController::class, 'resend'])
    ->middleware('throttle:otp-send')
    ->name('requests.resend');
Route::get('/verify-otp/{customerRequest}/success', [SellBuyController::class, 'success'])->name('requests.success');

Route::post('/enquiries', [EnquiryController::class, 'store'])
    ->middleware('throttle:public-forms')
    ->name('enquiries.store');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:public-forms')
    ->name('contact.store');

Route::get('/about', [StaticPageController::class, 'about'])->name('pages.about');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [StaticPageController::class, 'terms'])->name('pages.terms');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
