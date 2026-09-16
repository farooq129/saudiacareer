<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\JobModerationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SeekerPostModerationController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| Loaded twice by routes/web.php, once per language, exactly like the board —
| so an Arabic-reading moderator gets an Arabic admin at /admin and an
| English-reading one gets /en/admin. Same rules apply: never name a locale
| here, link with lroute(), and name every route including the POSTs.
|
| Two gates, not one:
|
|   ensure.staff  — admins and moderators. The review queues, the reports, the
|                   employer directory, and your own account.
|   ensure.admin  — admins only. Granting the verified badge, suspending
|                   accounts, and creating other staff.
|
| The split is deliberate: everything behind ensure.admin is either hard to
| undo or easy to abuse quietly. Approving an ad is neither — it is visible,
| reversible, and the moderator's actual job.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'ensure.staff'])
    ->group(function () {

        Route::get('/', DashboardController::class)->name('dashboard');

        /* ── Job listings ──────────────────────────────────────────────── */
        Route::get('jobs', [JobModerationController::class, 'index'])->name('jobs.index');
        Route::post('jobs/bulk', [JobModerationController::class, 'bulk'])->name('jobs.bulk');
        Route::get('jobs/{job}', [JobModerationController::class, 'show'])->name('jobs.show');
        Route::patch('jobs/{job}/approve', [JobModerationController::class, 'approve'])->name('jobs.approve');
        Route::patch('jobs/{job}/reject', [JobModerationController::class, 'reject'])->name('jobs.reject');
        Route::patch('jobs/{job}/unpublish', [JobModerationController::class, 'unpublish'])->name('jobs.unpublish');
        Route::patch('jobs/{job}/flags', [JobModerationController::class, 'flags'])->name('jobs.flags');
        Route::delete('jobs/{job}', [JobModerationController::class, 'destroy'])->name('jobs.destroy');

        /* ── Seeker posts ──────────────────────────────────────────────── */
        Route::get('seekers', [SeekerPostModerationController::class, 'index'])->name('seekers.index');
        Route::post('seekers/bulk', [SeekerPostModerationController::class, 'bulk'])->name('seekers.bulk');
        Route::get('seekers/{post}', [SeekerPostModerationController::class, 'show'])->name('seekers.show');
        Route::patch('seekers/{post}/approve', [SeekerPostModerationController::class, 'approve'])->name('seekers.approve');
        Route::patch('seekers/{post}/reject', [SeekerPostModerationController::class, 'reject'])->name('seekers.reject');
        Route::patch('seekers/{post}/unpublish', [SeekerPostModerationController::class, 'unpublish'])->name('seekers.unpublish');
        Route::delete('seekers/{post}', [SeekerPostModerationController::class, 'destroy'])->name('seekers.destroy');

        /* ── Reports ───────────────────────────────────────────────────── */
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::patch('reports/{report}/uphold', [ReportController::class, 'uphold'])->name('reports.uphold');
        Route::patch('reports/{report}/dismiss', [ReportController::class, 'dismiss'])->name('reports.dismiss');

        /* ── Employers ─────────────────────────────────────────────────────
           A moderator reviewing a listing needs to see who is behind it, so
           reading the directory is open to both. Granting the badge is not. */
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

        /* ── Your own account ──────────────────────────────────────────────
           Everyone who works here can change their own name and password. */
        Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('account', [AccountController::class, 'update'])->name('account.update');
        Route::put('account/password', [AccountController::class, 'updatePassword'])->name('account.password');

        /* ── Admin only ────────────────────────────────────────────────── */
        Route::middleware('ensure.admin')->group(function () {
            Route::patch('companies/{company}/verify', [CompanyController::class, 'verify'])->name('companies.verify');
            Route::patch('companies/{company}/unverify', [CompanyController::class, 'unverify'])->name('companies.unverify');

            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::patch('users/{user}/active', [UserController::class, 'toggleActive'])->name('users.active');

            Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
            Route::patch('staff/{user}/role', [StaffController::class, 'updateRole'])->name('staff.role');
            Route::patch('staff/{user}/password', [StaffController::class, 'resetPassword'])->name('staff.password');
            Route::delete('staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');
        });
    });
