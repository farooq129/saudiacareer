<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| Registered in both languages the same way the board is, so a visitor who was
| reading English does not get thrown into an Arabic sign-in form halfway
| through saving an ad.
|
| Sign-up is phone-first: most seekers arrive with a mobile number and no email,
| so the phone is the identifier and the email is optional. See the users table
| migration.
|
*/

Route::middleware(SetLocale::class)->group(function () {
    Route::name('ar.')->group(base_path('routes/auth-pages.php'));

    Route::prefix('en')->name('en.')->group(base_path('routes/auth-pages.php'));
});
