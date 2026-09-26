<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Client\JobController;
use App\Http\Controllers\Admin\AdminController;

/*
|--------------------------------------------------------------------------
| Public & Guest Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/services', function () {
    return view('services');
})->name('services');

// Auth Views
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Auth Handlers
Route::post('/register/client', [AuthController::class, 'registerClient'])->name('register.client');
Route::post('/register/maid', [AuthController::class, 'registerMaid'])->name('register.maid');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Client & Maid Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Client Dashboard & Actions
    Route::get('/client/dashboard', function () {
        return view('client.dashboard');
    })->name('client.dashboard');

    Route::post('/jobs/create', [JobController::class, 'store'])->name('jobs.store');

    // Maid Dashboard & Gig Actions
    Route::get('/maid/dashboard', function () {
        return view('maid.dashboard');
    })->name('maid.dashboard');

    Route::prefix('maid')->name('maid.')->group(function () {
        Route::get('/gigs',              [\App\Http\Controllers\Maid\GigController::class, 'index'])->name('gigs.index');
        Route::get('/gigs/{job}',        [\App\Http\Controllers\Maid\GigController::class, 'show'])->name('gigs.show');
        Route::post('/gigs/{job}/claim', [\App\Http\Controllers\Maid\GigController::class, 'claim'])->name('gigs.claim');
        Route::post('/gigs/{job}/release', [\App\Http\Controllers\Maid\GigController::class, 'release'])->name('gigs.release');
    });
});
/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/jobs/{job}/match', [AdminController::class, 'matchMaidToJob'])->name('admin.jobs.match');
    Route::delete('/jobs/{job}', [AdminController::class, 'deleteJob'])->name('admin.jobs.delete');
});