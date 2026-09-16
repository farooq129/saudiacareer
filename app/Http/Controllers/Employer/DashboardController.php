<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * An employer's front page: what their ads are doing, and who is waiting on a
 * reply. Both queues first, totals after — the same ordering as the admin's,
 * for the same reason.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $mine = fn () => Job::query()->where('user_id', $user->id);

        return view('employer.dashboard', [
            'company' => $user->company,

            'newApplications' => Application::query()
                ->whereNull('viewed_at')
                ->whereHas('job', fn ($q) => $q->where('user_id', $user->id))
                ->count(),

            'pending' => $mine()->where('status', Job::STATUS_PENDING)->count(),
            'rejected' => $mine()->where('status', Job::STATUS_REJECTED)->count(),
            'live' => $mine()->published()->count(),
            'drafts' => $mine()->where('status', Job::STATUS_DRAFT)->count(),

            'totalViews' => (int) $mine()->sum('views_count'),
            'totalApplications' => (int) $mine()->sum('applications_count'),

            'recentJobs' => $mine()
                ->with(['city', 'category'])
                ->withCount('applications')
                ->latest('created_at')
                ->take(6)
                ->get(),

            'recentApplications' => Application::query()
                ->whereHas('job', fn ($q) => $q->where('user_id', $user->id))
                ->with(['user', 'job'])
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }
}
