<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public menu routes (customer-facing, no auth needed)
Route::prefix('menu')->name('menu.')->group(function () {
    Route::get('{slug}', [App\Http\Controllers\Public\PublicMenuController::class, 'show'])
        ->name('show');
    Route::post('{slug}/order', [App\Http\Controllers\Public\PublicMenuController::class, 'placeOrder'])
        ->name('order.place')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('order/status/{orderRef}', [App\Http\Controllers\Public\PublicMenuController::class, 'orderStatus'])
        ->name('order.status');
});
