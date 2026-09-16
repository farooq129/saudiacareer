<?php

use App\Http\Controllers\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\Employer\CompanyController as EmployerCompanyController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\JobController as EmployerJobController;
use App\Http\Controllers\Seeker\ApplicationController as SeekerApplicationController;
use App\Http\Controllers\Seeker\DashboardController as SeekerDashboardController;
use App\Http\Controllers\Seeker\PostController as SeekerPostController;
use App\Http\Controllers\Seeker\ProfileController as SeekerProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Employer and seeker portals
|--------------------------------------------------------------------------
|
| Loaded twice by routes/web.php, once per language, like everything else.
| Each area is gated on the coarse users.role column; ownership of an
| individual row is checked inside the controller, because "is this yours" is
| a question about data, not about the route.
|
*/

/* ── Employer ──────────────────────────────────────────────────────────── */
Route::prefix('employer')
    ->name('employer.')
    ->middleware(['auth', 'ensure.employer'])
    ->group(function () {

        Route::get('/', EmployerDashboardController::class)->name('dashboard');

        Route::get('jobs', [EmployerJobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/new', [EmployerJobController::class, 'create'])->name('jobs.create');
        Route::post('jobs', [EmployerJobController::class, 'store'])->name('jobs.store');
        Route::get('jobs/{job}/edit', [EmployerJobController::class, 'edit'])->name('jobs.edit');
        Route::put('jobs/{job}', [EmployerJobController::class, 'update'])->name('jobs.update');
        Route::patch('jobs/{job}/close', [EmployerJobController::class, 'close'])->name('jobs.close');
        Route::delete('jobs/{job}', [EmployerJobController::class, 'destroy'])->name('jobs.destroy');

        Route::get('applications', [EmployerApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [EmployerApplicationController::class, 'show'])->name('applications.show');
        Route::patch('applications/{application}', [EmployerApplicationController::class, 'updateStatus'])->name('applications.status');

        Route::get('company', [EmployerCompanyController::class, 'edit'])->name('company.edit');
        Route::put('company', [EmployerCompanyController::class, 'update'])->name('company.update');
    });

/* ── Seeker ────────────────────────────────────────────────────────────── */
Route::prefix('seeker')
    ->name('seeker.')
    ->middleware(['auth', 'ensure.seeker'])
    ->group(function () {

        Route::get('/', SeekerDashboardController::class)->name('dashboard');

        Route::get('posts', [SeekerPostController::class, 'index'])->name('posts.index');
        Route::get('posts/new', [SeekerPostController::class, 'create'])->name('posts.create');
        Route::post('posts', [SeekerPostController::class, 'store'])->name('posts.store');
        Route::get('posts/{post}/edit', [SeekerPostController::class, 'edit'])->name('posts.edit');
        Route::put('posts/{post}', [SeekerPostController::class, 'update'])->name('posts.update');
        Route::delete('posts/{post}', [SeekerPostController::class, 'destroy'])->name('posts.destroy');

        Route::get('applications', [SeekerApplicationController::class, 'index'])->name('applications.index');

        Route::get('profile', [SeekerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [SeekerProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [SeekerProfileController::class, 'updatePassword'])->name('profile.password');
    });
