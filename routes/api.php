<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile App API (v1)
|--------------------------------------------------------------------------
|
| JSON endpoints used by the Yo-gig House Manager Flutter app. Content and
| bookings are the same records the website shows; clients, maids and the
| super admin authenticate with a bearer token issued at login.
|
*/

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('/config', [V1\ContentController::class, 'config'])->name('config');
    Route::get('/pages/{page}', [V1\ContentController::class, 'page'])->name('pages.show');
    Route::get('/services', [V1\ContentController::class, 'services'])->name('services.index');
    Route::get('/services/{service}', [V1\ContentController::class, 'service'])->name('services.show');
    Route::get('/testimonials', [V1\ContentController::class, 'testimonials'])->name('testimonials.index');
    Route::post('/contact', [V1\ContentController::class, 'contact'])->middleware('throttle:5,1')->name('contact');

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/auth/login', [V1\AuthController::class, 'login'])->name('auth.login');
        Route::post('/auth/register/client', [V1\AuthController::class, 'registerClient'])->name('auth.register.client');
        Route::post('/auth/register/maid', [V1\AuthController::class, 'registerMaid'])->name('auth.register.maid');
    });

    Route::middleware(['auth:api', 'api.role'])->group(function () {
        Route::post('/auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [V1\ProfileController::class, 'show'])->name('me');
        Route::put('/me', [V1\ProfileController::class, 'update'])->name('me.update');
        Route::put('/me/password', [V1\ProfileController::class, 'updatePassword'])->name('me.password');
    });

    Route::middleware(['auth:api', 'api.role:client'])->prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', [V1\Client\BookingController::class, 'dashboard'])->name('dashboard');
        Route::get('/bookings', [V1\Client\BookingController::class, 'index'])->name('bookings.index');
        Route::post('/bookings', [V1\Client\BookingController::class, 'store'])->name('bookings.store');
        Route::get('/bookings/{job}', [V1\Client\BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{job}/approve', [V1\Client\BookingController::class, 'approve'])->name('bookings.approve');
        Route::post('/bookings/{job}/cancel', [V1\Client\BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{job}/payments', [V1\Client\BookingController::class, 'pay'])->name('bookings.payments.store');
    });

    Route::middleware(['auth:api', 'api.role:maid'])->prefix('maid')->name('maid.')->group(function () {
        Route::get('/dashboard', [V1\Maid\GigController::class, 'dashboard'])->name('dashboard');
        Route::get('/gigs', [V1\Maid\GigController::class, 'index'])->name('gigs.index');
        Route::get('/jobs', [V1\Maid\GigController::class, 'mine'])->name('jobs.index');
        Route::get('/gigs/{job}', [V1\Maid\GigController::class, 'show'])->name('gigs.show');
        Route::post('/gigs/{job}/claim', [V1\Maid\GigController::class, 'claim'])->name('gigs.claim');
        Route::post('/gigs/{job}/release', [V1\Maid\GigController::class, 'release'])->name('gigs.release');
        Route::post('/gigs/{job}/start', [V1\Maid\GigController::class, 'start'])->name('gigs.start');
        Route::post('/gigs/{job}/complete', [V1\Maid\GigController::class, 'complete'])->name('gigs.complete');
        Route::get('/earnings', [V1\Maid\EarningsController::class, 'index'])->name('earnings');
        Route::put('/earnings/payout-details', [V1\Maid\EarningsController::class, 'updatePayoutDetails'])->name('earnings.payout-details');
    });

    Route::middleware(['auth:api', 'api.role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', V1\Admin\DashboardController::class)->name('dashboard');

        Route::get('/bookings', [V1\Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{job}', [V1\Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{job}/assign', [V1\Admin\BookingController::class, 'assign'])->name('bookings.assign');
        Route::post('/bookings/{job}/status', [V1\Admin\BookingController::class, 'updateStatus'])->name('bookings.status');
        Route::delete('/bookings/{job}', [V1\Admin\BookingController::class, 'destroy'])->name('bookings.destroy');

        Route::get('/payments', [V1\Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/confirm', [V1\Admin\PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::post('/payments/{payment}/reject', [V1\Admin\PaymentController::class, 'reject'])->name('payments.reject');

        Route::get('/payouts', [V1\Admin\PayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{payout}/paid', [V1\Admin\PayoutController::class, 'markPaid'])->name('payouts.paid');
        Route::post('/payouts/{payout}/failed', [V1\Admin\PayoutController::class, 'markFailed'])->name('payouts.failed');

        Route::get('/users', [V1\Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [V1\Admin\UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/national-id', [V1\Admin\UserController::class, 'nationalId'])->name('users.national-id');
        Route::post('/users/{user}/verification', [V1\Admin\UserController::class, 'verify'])->name('users.verify');
        Route::post('/users/{user}/toggle-active', [V1\Admin\UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::apiResource('services', V1\Admin\ServiceController::class)->except('show');
        Route::apiResource('sections', V1\Admin\PageSectionController::class)->except('show');
        Route::apiResource('testimonials', V1\Admin\TestimonialController::class)->except('show');

        Route::get('/settings', [V1\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings/{group}', [V1\Admin\SettingController::class, 'update'])->name('settings.update');

        Route::get('/messages', [V1\Admin\MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [V1\Admin\MessageController::class, 'show'])->name('messages.show');
        Route::delete('/messages/{message}', [V1\Admin\MessageController::class, 'destroy'])->name('messages.destroy');
    });
});
