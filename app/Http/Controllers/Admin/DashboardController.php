<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobReport;
use App\Models\JobSeekerPost;
use App\Models\User;
use Illuminate\View\View;

/**
 * The admin front page.
 *
 * It answers one question first — what is waiting on me — and only then shows
 * the totals. A moderation dashboard that opens on a chart of last month's
 * signups buries the queue that is actually the job.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            // The queue, and the two things that sit in it.
            'pendingJobs' => Job::query()->awaitingReview()->count(),
            'pendingSeekerPosts' => JobSeekerPost::query()->awaitingReview()->count(),
            'openReports' => JobReport::query()->open()->count(),
            'unverifiedCompanies' => Company::query()->where('is_verified', false)->count(),

            // The board at a glance.
            'liveJobs' => Job::query()->published()->count(),
            'livePosts' => JobSeekerPost::query()->published()->count(),
            'employers' => User::query()->where('role', User::ROLE_EMPLOYER)->count(),
            'seekers' => User::query()->where('role', User::ROLE_SEEKER)->count(),
            'applications' => Application::query()->count(),

            // Enough of each queue to act on without leaving this page.
            'latestPendingJobs' => Job::query()
                ->awaitingReview()
                ->with(['company', 'city', 'category', 'user'])
                ->take(6)
                ->get(),

            'latestPendingPosts' => JobSeekerPost::query()
                ->awaitingReview()
                ->with(['city', 'category', 'user'])
                ->take(6)
                ->get(),

            'latestReports' => JobReport::query()
                ->open()
                ->with(['job.company', 'user'])
                ->oldest()
                ->take(6)
                ->get(),
        ]);
    }
}
