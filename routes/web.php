<?php

use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (publik)
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/books', [CatalogController::class, 'index'])->name('books.index');
Route::get('/books/{product}', [CatalogController::class, 'show'])->name('books.show');
Route::get('/store/{store}', [CatalogController::class, 'store'])->name('stores.show');
Route::view('/help', 'pages.help')->name('help');

// Saran kata, cari dengan gambar, dan asisten AI (JSON, dipanggil dari JS halaman).
Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('search.suggest');
Route::post('/search/image', [SearchController::class, 'image'])->middleware('throttle:10,1')->name('search.image');
Route::view('/assistant', 'assistant.index')->name('assistant');
Route::post('/assistant', AssistantController::class)->middleware('throttle:20,1')->name('assistant.ask');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Pembeli (wajib login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/review', [OrderController::class, 'review'])->name('orders.review');
});

/*
|--------------------------------------------------------------------------
| kyreadSeller (panel penjual)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('seller')->name('seller.')->group(function () {
    Route::get('/open-store', [Seller\StoreController::class, 'create'])->name('store.create');
    Route::post('/open-store', [Seller\StoreController::class, 'store'])->name('store.store');

    Route::middleware('seller')->group(function () {
        Route::get('/', Seller\DashboardController::class)->name('dashboard');
        Route::resource('products', Seller\ProductController::class)->except('show');
        Route::get('/orders', [Seller\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Seller\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [Seller\OrderController::class, 'update'])->name('orders.update');
        Route::get('/store', [Seller\StoreController::class, 'edit'])->name('store.edit');
        Route::put('/store', [Seller\StoreController::class, 'update'])->name('store.update');
        Route::view('/faq', 'seller.faq')->name('faq');
    });
});
