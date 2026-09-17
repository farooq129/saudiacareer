<?php

use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\CategoryController;
use App\Http\Controllers\Public\CityController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\JobController;
use App\Http\Controllers\Public\SavedJobController;
use App\Http\Controllers\Public\SeekerPostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Board routes
|--------------------------------------------------------------------------
|
| Loaded twice by routes/web.php — once bare for Arabic, once under /en for
| English — so every page here exists in both languages at its own URL. Do not
| name a locale in this file, and do not call route() with an 'ar.' or 'en.'
| prefix from anywhere: use lroute(), which resolves against the request.
|
| Slugs are language-neutral (derived from the English title), so one listing
| has one slug in both languages and a shared link survives a language switch.
|
*/

Route::get('/', HomeController::class)->name('home');

Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
Route::post('jobs/{job}/report', [JobController::class, 'report'])->name('jobs.report');

// Applying needs an account, unlike saving: an application carries a CV and a
// name to an employer, who needs someone real to reply to.
Route::post('jobs/{job}/apply', [ApplicationController::class, 'store'])
    ->middleware('auth')
    ->name('jobs.apply');

Route::get('categories/{category}', CategoryController::class)->name('categories.show');
Route::get('cities/{city}', CityController::class)->name('cities.show');

/*
 | The blog. The section page is its own URL rather than ?section=, because it
 | is a page a crawler should index in its own right — a filtered feed with a
 | heading and a description, not a view state of the index.
 */
Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/section/{blogCategory}', [BlogController::class, 'index'])->name('blog.section');
Route::get('blog/{post}', [BlogController::class, 'show'])->name('blog.show');

Route::get('seekers', [SeekerPostController::class, 'index'])->name('seekers.index');
Route::get('seekers/{post}', [SeekerPostController::class, 'show'])->name('seekers.show');

// Saved ads work signed out too — the list lives in the session until the
// visitor signs in, at which point it is merged into saved_jobs.
Route::get('saved', [SavedJobController::class, 'index'])->name('saved.index');
Route::post('saved/{job}', [SavedJobController::class, 'store'])->name('saved.store');
Route::delete('saved/{job}', [SavedJobController::class, 'destroy'])->name('saved.destroy');
