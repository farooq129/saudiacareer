<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

/*
| Loaded twice by routes/auth.php, once per language. Same rule as
| routes/board.php: never name a locale here, and link with lroute().
|
| Every route is named, including the POSTs. An unnamed route inside a
| ->name('en.') group is registered as plain "en.", which then collides with
| the next unnamed one and makes Route::has() answer nonsense.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.attempt');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('register.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');
