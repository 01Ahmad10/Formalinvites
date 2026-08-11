<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('events', EventController::class)->except('destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('customers', [AdminController::class, 'customers'])->name('customers.index');
    Route::post('customers', [AdminController::class, 'storeCustomer'])->name('customers.store');
    Route::put('customers/{customer}', [AdminController::class, 'updateCustomer'])->name('customers.update');
    Route::get('users', [AdminController::class, 'users'])->name('users.index');
    Route::post('users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('packages', [AdminController::class, 'packages'])->name('packages.index');
    Route::post('packages', [AdminController::class, 'storePackage'])->name('packages.store');
    Route::put('packages/{package}', [AdminController::class, 'updatePackage'])->name('packages.update');
    Route::get('payments', [AdminController::class, 'payments'])->name('payments.index');
    Route::post('payments', [AdminController::class, 'storePayment'])->name('payments.store');
    Route::get('payments/{payment}', [AdminController::class, 'showPayment'])->name('payments.show');
    Route::post('payments/{payment}/transactions', [AdminController::class, 'storePaymentTransaction'])->name('payments.transactions.store');
    Route::put('payments/{payment}/discount', [AdminController::class, 'updatePaymentDiscount'])->name('payments.discount.update');
    Route::put('payments/{payment}/coupon', [AdminController::class, 'applyPaymentCoupon'])->name('payments.coupon.update');
    Route::patch('payments/{payment}/confirm', [AdminController::class, 'confirmPayment'])->name('payments.confirm');
    Route::get('coupons', [AdminController::class, 'coupons'])->name('coupons.index');
    Route::post('coupons', [AdminController::class, 'storeCoupon'])->name('coupons.store');
    Route::put('coupons/{coupon}', [AdminController::class, 'updateCoupon'])->name('coupons.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
