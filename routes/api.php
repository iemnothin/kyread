<?php

use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1: dipakai aplikasi Android kyread (autentikasi token Sanctum)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/meta', [CatalogController::class, 'meta'])->name('meta');
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
    Route::get('/products/{product}', [CatalogController::class, 'show'])->name('products.show');

    Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('search.suggest');
    Route::post('/search/image', [SearchController::class, 'image'])->middleware('throttle:10,1')->name('search.image');
    Route::post('/assistant', AssistantController::class)->middleware('throttle:20,1')->name('assistant');

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
        Route::post('/auth/register', [AuthController::class, 'register'])->name('register');
        Route::post('/auth/google', [AuthController::class, 'google'])->name('google');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/me', [AuthController::class, 'update'])->name('me.update'); // POST agar mendukung upload avatar (multipart)
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

        Route::post('/checkout', [OrderController::class, 'checkout'])->name('checkout');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
        Route::post('/orders/{order}/review', [OrderController::class, 'review'])->name('orders.review');
    });
});
