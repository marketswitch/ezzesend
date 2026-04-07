<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ── Add to routes/user.php inside the auth middleware group ──
|
| Paste the block below inside:
|   Route::middleware('auth')->name('user.')->group(function () {
|       // ... existing routes ...
|       // ADD BELOW:
|--------------------------------------------------------------------------
*/

// ══════════════════════════════════════════════════════════════════════
// DIGITAL MENU — Authenticated (restaurant owner/staff)
// ══════════════════════════════════════════════════════════════════════

Route::prefix('menu')->name('menu.')->middleware(['auth', 'has.subscription'])->group(function () {

    // Restaurant setup
    Route::controller('User\Menu\MenuBuilderController')->group(function () {
        Route::get('setup',       'setup')       ->name('setup');
        Route::post('setup',      'setupStore')  ->name('setup.store');

        // Categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/',              'categories')    ->name('index');
            Route::post('store',         'categoryStore') ->name('store');
            Route::post('update/{id}',   'categoryUpdate')->name('update');
            Route::post('delete/{id}',   'categoryDelete')->name('delete');
            Route::post('reorder',       'categoryReorder')->name('reorder');
        });

        // Items
        Route::prefix('items')->name('items.')->group(function () {
            Route::get('/',                           'items')                   ->name('index');
            Route::post('store',                      'itemStore')               ->name('store');
            Route::post('update/{id}',                'itemUpdate')              ->name('update');
            Route::post('delete/{id}',                'itemDelete')              ->name('delete');
            Route::post('toggle-availability/{id}',   'itemToggleAvailability')  ->name('toggle');
        });

        // Tables & QR
        Route::prefix('tables')->name('tables.')->group(function () {
            Route::get('/',                        'tables')              ->name('index');
            Route::post('branch/store',            'branchStore')         ->name('branch.store');
            Route::post('branch/{branchId}/table', 'tableStore')          ->name('store');
            Route::post('regenerate-qr/{tableId}', 'tableRegenerateQr')   ->name('regenerate');
        });
    });

    // Orders
    Route::controller('User\Menu\MenuOrderController')->prefix('orders')->name('orders.')->group(function () {
        Route::get('/',                    'index')        ->name('index');
        Route::get('{orderId}',            'show')         ->name('show');
        Route::post('{orderId}/status',    'updateStatus') ->name('status');
        Route::get('analytics/report',     'analytics')    ->name('analytics');
    });
});


/*
|--------------------------------------------------------------------------
| ── Add to routes/web.php (public, no auth) ──
|--------------------------------------------------------------------------
*/

// ══════════════════════════════════════════════════════════════════════
// PUBLIC MENU — No authentication required (customer-facing)
// ⚡ SECURITY: These routes are rate-limited and CSRF-exempt for the
//    order endpoint (uses idempotency key instead of CSRF session token,
//    since customers have no session).
// ══════════════════════════════════════════════════════════════════════

Route::prefix('menu')->name('menu.')->group(function () {
    // Customer views the menu
    Route::get('{slug}', [App\Http\Controllers\Public\PublicMenuController::class, 'show'])
        ->name('show');

    // Customer places an order
    // ⚡ Rate limited: 5 orders / 10 min per IP (enforced in controller)
    Route::post('{slug}/order', [App\Http\Controllers\Public\PublicMenuController::class, 'placeOrder'])
        ->name('order.place')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    // Customer polls order status
    Route::get('order/status/{orderRef}', [App\Http\Controllers\Public\PublicMenuController::class, 'orderStatus'])
        ->name('order.status');
});


/*
|--------------------------------------------------------------------------
| ── Add to app/Http/Kernel.php (or bootstrap/app.php in Laravel 11) ──
|
|   $middleware->alias([
|       // ... existing ...
|       'menu.owner' => \App\Http\Middleware\MenuOwnership::class,
|   ]);
|--------------------------------------------------------------------------

|--------------------------------------------------------------------------
| ── Add to app/Constants/FileInfo.php inside fileInfo() ──
|
|   $data['menuLogo'] = [
|       'path' => 'assets/images/menu/logo',
|       'size' => '400x400',
|   ];
|   $data['menuCover'] = [
|       'path' => 'assets/images/menu/cover',
|       'size' => '1200x400',
|   ];
|   $data['menuCategory'] = [
|       'path' => 'assets/images/menu/category',
|       'size' => '400x400',
|   ];
|   $data['menuItem'] = [
|       'path' => 'assets/images/menu/item',
|       'size' => '600x600',
|   ];
|--------------------------------------------------------------------------
*/
