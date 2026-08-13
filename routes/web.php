<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GuestManagementController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\PublicRsvpController;
use App\Http\Controllers\EventActivityController;
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
Route::get('rsvp/{token}', [PublicRsvpController::class, 'show'])->name('public.rsvp.show');
Route::post('rsvp/{token}', [PublicRsvpController::class, 'submit'])->name('public.rsvp.submit');

Route::middleware('auth')->group(function () {
    Route::resource('events', EventController::class)->except('destroy');
    Route::get('events/{event}/activities', [EventActivityController::class, 'index'])->name('events.activities.index');
    Route::get('events/{event}/activities/create', [EventActivityController::class, 'create'])->name('events.activities.create');
    Route::post('events/{event}/activities', [EventActivityController::class, 'store'])->name('events.activities.store');
    Route::get('events/{event}/activities/{activity}', [EventActivityController::class, 'show'])->name('events.activities.show');
    Route::get('events/{event}/activities/{activity}/edit', [EventActivityController::class, 'edit'])->name('events.activities.edit');
    Route::put('events/{event}/activities/{activity}', [EventActivityController::class, 'update'])->name('events.activities.update');
    Route::patch('events/{event}/activities/{activity}/active', [EventActivityController::class, 'setActive'])->name('events.activities.active');
    Route::get('events/{event}/guests', [GuestManagementController::class, 'index'])->name('events.guests.index');
    Route::post('events/{event}/guests', [GuestManagementController::class, 'storeParty'])->name('events.guests.store');
    Route::get('events/{event}/guests/{party}', [GuestManagementController::class, 'showParty'])->name('events.guests.show');
    Route::put('events/{event}/guests/{party}', [GuestManagementController::class, 'updateParty'])->name('events.guests.update');
    Route::patch('events/{event}/guests/{party}/active', [GuestManagementController::class, 'setPartyActive'])->name('events.guests.active');
    Route::post('events/{event}/guests/{party}/members', [GuestManagementController::class, 'storeMember'])->name('events.guests.members.store');
    Route::put('events/{event}/guests/{party}/members/{member}', [GuestManagementController::class, 'updateMember'])->name('events.guests.members.update');
    Route::delete('events/{event}/guests/{party}/members/{member}', [GuestManagementController::class, 'destroyMember'])->name('events.guests.members.destroy');
    Route::get('events/{event}/rsvps', [RsvpController::class, 'index'])->name('events.rsvps.index');
    Route::get('events/{event}/rsvps/{party}', [RsvpController::class, 'show'])->name('events.rsvps.show');
    Route::post('events/{event}/meals', [RsvpController::class, 'storeMeal'])->name('events.meals.store');
    Route::put('events/{event}/meals/{meal}', [RsvpController::class, 'updateMeal'])->name('events.meals.update');
    Route::patch('events/{event}/meals/{meal}/active', [RsvpController::class, 'setMealActive'])->name('events.meals.active');
    Route::patch('events/{event}/guests/{party}/rsvp-token', [RsvpController::class, 'regenerateToken'])->name('events.guests.rsvp-token');
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
