<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// --- Public ---
Route::get('/', [HomeController::class, 'index'])->name('home');

// Products
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Static pages
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('page');

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware(['guest', 'throttle:5,1']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware(['guest', 'throttle:3,60']);

// --- Cart (guest-accessible) ---
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// --- AJAX endpoints ---
Route::prefix('ajax')->name('ajax.')->group(function () {
    Route::get('/products', [ProductController::class, 'ajaxIndex'])->name('products');
    Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
    Route::get('/cart/data', [CartController::class, 'data'])->name('cart.data');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/item/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/item/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::get('/wishlist/count', [WishlistController::class, 'count'])->name('wishlist.count');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->middleware('auth')->name('wishlist.toggle');
    Route::post('/newsletter/subscribe', [HomeController::class, 'newsletterSubscribe'])->name('newsletter.subscribe');
});

// --- Authenticated ---
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile/edit', [DashboardController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [DashboardController::class, 'update'])->name('profile.update');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/checkout/{gateway}/callback', [CheckoutController::class, 'handlePaymentCallback'])->name('checkout.callback');
    Route::get('/checkout/{gateway}/cancel', [CheckoutController::class, 'handlePaymentCancel'])->name('checkout.cancel');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Wallet
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('/wallet/add-balance', [WalletController::class, 'addBalance'])->name('wallet.add.balance');
    Route::post('/wallet/redeem-code', [WalletController::class, 'redeemCode'])->name('wallet.redeem.code');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');

    // Addresses
    Route::get('/addresses',                  [AddressController::class, 'index'])->name('addresses.index');
    Route::get('/addresses/create',           [AddressController::class, 'create'])->name('addresses.create');
    Route::post('/addresses',                 [AddressController::class, 'store'])->name('addresses.store');
    Route::get('/addresses/{address}/edit',   [AddressController::class, 'edit'])->name('addresses.edit');
    Route::put('/addresses/{address}',        [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}',     [AddressController::class, 'destroy'])->name('addresses.destroy');

    // Prescriptions
    Route::get('/prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
    Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
    Route::get('/prescriptions/{prescription}/file', [PrescriptionController::class, 'file'])->name('prescriptions.file');
});
