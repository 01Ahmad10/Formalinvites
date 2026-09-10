<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GuestManagementController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\PublicRsvpController;
use App\Http\Controllers\PublicInvitationController;
use App\Http\Controllers\EventActivityController;
use App\Http\Controllers\EventInvitationController;
use App\Http\Controllers\AdminTemplateController;
use App\Http\Controllers\EventPublicationController;
use App\Http\Controllers\EventSetupController;
use App\Http\Controllers\EventBuilderController;
use App\Http\Controllers\TemplateDemoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
    ]);
});

Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');
// Local visual reconstruction specimens; no customer data or RSVP endpoint.
Route::get('/template-demos/assets/{template}/{role}', [TemplateDemoController::class, 'asset'])
    ->where('template', '[1-7]')->where('role', '[A-Za-z][A-Za-z0-9]*')->name('template-demos.assets');
Route::get('/template-demos/{template?}', TemplateDemoController::class)->name('template-demos');
Route::get('rsvp/{token}', [PublicRsvpController::class, 'show'])->middleware('throttle:60,1')->name('public.rsvp.show');
Route::post('rsvp/{token}', [PublicRsvpController::class, 'submit'])->middleware('throttle:10,1')->name('public.rsvp.submit');
Route::get('invite/{token}', [PublicInvitationController::class, 'show'])->middleware('throttle:60,1')->name('public.invitation.show');

Route::middleware('auth')->group(function () {
    Route::get('events/start', [EventController::class, 'start'])->name('events.start');
    Route::resource('events', EventController::class)->except('destroy');
    Route::get('events/{event}/activities', [EventActivityController::class, 'index'])->name('events.activities.index');
    Route::get('events/{event}/activities/create', [EventActivityController::class, 'create'])->name('events.activities.create');
    Route::post('events/{event}/activities', [EventActivityController::class, 'store'])->name('events.activities.store');
    Route::get('events/{event}/activities/{activity}', [EventActivityController::class, 'show'])->name('events.activities.show');
    Route::get('events/{event}/activities/{activity}/edit', [EventActivityController::class, 'edit'])->name('events.activities.edit');
    Route::put('events/{event}/activities/{activity}', [EventActivityController::class, 'update'])->name('events.activities.update');
    Route::patch('events/{event}/activities/{activity}/active', [EventActivityController::class, 'setActive'])->name('events.activities.active');
    Route::get('events/{event}/invitation', [EventInvitationController::class, 'edit'])->name('events.invitation.edit');
    Route::put('events/{event}/invitation', [EventInvitationController::class, 'update'])->name('events.invitation.update');
    Route::get('events/{event}/invitation/preview', [EventInvitationController::class, 'preview'])->name('events.invitation.preview');
    Route::get('events/{event}/invitation/live-preview', [EventPublicationController::class, 'livePreview'])->name('events.invitation.live-preview');
    Route::get('events/{event}/setup', [EventSetupController::class, 'show'])->name('events.setup');
    Route::patch('events/{event}/setup/{step}', [EventSetupController::class, 'save'])->name('events.setup.save');
    Route::get('events/{event}/builder', [EventBuilderController::class, 'show'])->name('events.builder');
    Route::put('events/{event}/builder', [EventBuilderController::class, 'update'])->name('events.builder.update');
    Route::post('events/{event}/publication/publish', [EventPublicationController::class, 'publish'])->name('events.publication.publish');
    Route::patch('events/{event}/publication/disable', [EventPublicationController::class, 'disable'])->name('events.publication.disable');
    Route::patch('events/{event}/publication/enable', [EventPublicationController::class, 'enable'])->name('events.publication.enable');
    Route::patch('events/{event}/publication/archive', [EventPublicationController::class, 'archive'])->name('events.publication.archive');
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
    Route::get('events/{event}/meals', [RsvpController::class, 'meals'])->name('events.meals.index');
    Route::post('events/{event}/meals', [RsvpController::class, 'storeMeal'])->name('events.meals.store');
    Route::put('events/{event}/meals/{meal}', [RsvpController::class, 'updateMeal'])->name('events.meals.update');
    Route::patch('events/{event}/meals/{meal}/active', [RsvpController::class, 'setMealActive'])->name('events.meals.active');
    Route::patch('events/{event}/guests/{party}/rsvp-token', [RsvpController::class, 'regenerateToken'])->name('events.guests.rsvp-token');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('customers', [AdminController::class, 'customers'])->name('customers.index');
    Route::post('customers', [AdminController::class, 'storeCustomer'])->name('customers.store');
    Route::post('clients', [AdminController::class, 'storeClient'])->name('clients.store');
    Route::get('customers/{customer}', [AdminController::class, 'showCustomer'])->name('customers.show');
    Route::put('customers/{customer}', [AdminController::class, 'updateCustomer'])->name('customers.update');
    Route::post('customers/{customer}/second-login', [AdminController::class, 'storeSecondLogin'])->name('customers.second-login.store');
    Route::post('customers/{customer}/invitations', [AdminController::class, 'startInvitation'])->name('customers.invitations.start');
    Route::post('customers/{customer}/invitation-entitlements', [AdminController::class, 'storeInvitationEntitlement'])->name('customers.invitation-entitlements.store');
    Route::patch('customers/{customer}/invitation-entitlements/{entitlement}', [AdminController::class, 'updateInvitationEntitlement'])->name('customers.invitation-entitlements.update');
    Route::put('customer-users/{user}/password', [AdminController::class, 'resetCustomerUserPassword'])->name('customer-users.password.update');
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
    Route::get('templates', [AdminTemplateController::class, 'index'])->name('templates.index');
    Route::patch('templates/{template}/active', [AdminTemplateController::class, 'setActive'])->name('templates.active');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
