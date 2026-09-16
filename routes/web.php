<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The board, in two languages
|--------------------------------------------------------------------------
|
| routes/board.php holds every page exactly once. It is loaded twice here:
| unprefixed for Arabic (the default language, served from the bare root) and
| under /en for English. Each copy gets its own route-name prefix, so
| `ar.jobs.show` and `en.jobs.show` are the same page in the two languages.
|
| Both are real, crawlable URLs — neither language is a query-string variant of
| the other — which is what lets an Arabic listing and its English translation
| each rank on their own.
|
| Views never name a prefix; they call lroute('jobs.show', $job), which resolves
| against the language the request is running in. SetLocale reads the language
| back off the prefix, so the URL stays the single source of truth.
|
*/

Route::middleware(SetLocale::class)->group(function () {
    Route::name('ar.')->group(function () {
        require base_path('routes/board.php');
        require base_path('routes/admin.php');
        require base_path('routes/portal.php');
    });

    Route::prefix('en')->name('en.')->group(function () {
        require base_path('routes/board.php');
        require base_path('routes/admin.php');
        require base_path('routes/portal.php');
    });
});

require __DIR__.'/auth.php';

/*
| A URL that matches nothing never enters the web group, so it has no session —
| and the board's chrome (the saved-ads badge, the CSRF token, the sign-in
| state) needs one. A fallback route is a matched route, so the 404 renders as
| a real page with the header and footer on it instead of a bare framework
| error.
*/
Route::fallback(function () {
    abort(404);
})->middleware(SetLocale::class);
