<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Client;
use App\Http\Controllers\Maid;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Guest Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'home'])->name('landing');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendMessage'])->middleware('throttle:5,1')->name('contact.send');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');

    Route::post('/register/client', [AuthController::class, 'registerClient'])->name('register.client');
    Route::post('/register/maid', [AuthController::class, 'registerMaid'])->name('register.maid');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,client,maid'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', Client\DashboardController::class)->name('dashboard');

    Route::get('/bookings/create', [Client\BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [Client\BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{job}', [Client\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{job}/approve', [Client\BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{job}/cancel', [Client\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{job}/payments', [Client\PaymentController::class, 'store'])->name('bookings.payments.store');
});

Route::middleware(['auth', 'role:maid'])->prefix('maid')->name('maid.')->group(function () {
    Route::get('/dashboard', Maid\DashboardController::class)->name('dashboard');

    Route::get('/gigs', [Maid\GigController::class, 'index'])->name('gigs.index');
    Route::get('/gigs/{job}', [Maid\GigController::class, 'show'])->name('gigs.show');
    Route::post('/gigs/{job}/claim', [Maid\GigController::class, 'claim'])->name('gigs.claim');
    Route::post('/gigs/{job}/release', [Maid\GigController::class, 'release'])->name('gigs.release');
    Route::post('/gigs/{job}/start', [Maid\GigController::class, 'start'])->name('gigs.start');
    Route::post('/gigs/{job}/complete', [Maid\GigController::class, 'complete'])->name('gigs.complete');

    Route::get('/earnings', [Maid\EarningsController::class, 'index'])->name('earnings');
    Route::put('/earnings/payout-details', [Maid\EarningsController::class, 'updatePayoutDetails'])->name('earnings.payout-details');
});

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{job}', [Admin\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{job}/assign', [Admin\BookingController::class, 'assign'])->name('bookings.assign');
    Route::post('/bookings/{job}/status', [Admin\BookingController::class, 'updateStatus'])->name('bookings.status');
    Route::delete('/bookings/{job}', [Admin\BookingController::class, 'destroy'])->name('bookings.destroy');

    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/confirm', [Admin\PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('/payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');

    Route::get('/payouts', [Admin\PayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{payout}/paid', [Admin\PayoutController::class, 'markPaid'])->name('payouts.paid');
    Route::post('/payouts/{payout}/failed', [Admin\PayoutController::class, 'markFailed'])->name('payouts.failed');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/national-id', [Admin\UserController::class, 'nationalId'])->name('users.national-id');
    Route::post('/users/{user}/verification', [Admin\UserController::class, 'verify'])->name('users.verify');
    Route::post('/users/{user}/toggle-active', [Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');

    Route::resource('services', Admin\ServiceController::class)->except('show');
    Route::resource('sections', Admin\PageSectionController::class)->except('show');
    Route::resource('testimonials', Admin\TestimonialController::class)->except('show');

    Route::get('/settings/{group?}', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/{group}', [Admin\SettingController::class, 'update'])->name('settings.update');

    Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
    Route::delete('/messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');
});
