<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobSeekerPost;
use App\Services\SavedJobService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A seeker's front page.
 *
 * The thing a job seeker actually wants to know is whether anyone has looked at
 * them, so the counts that lead are shortlisted and viewed rather than "total
 * applications sent" — a number that only ever goes up and says nothing.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly SavedJobService $saved) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $mine = fn () => Application::query()->where('user_id', $user->id);

        return view('seeker.dashboard', [
            'shortlisted' => $mine()->where('status', Application::STATUS_SHORTLISTED)->count(),
            'viewed' => $mine()->whereNotNull('viewed_at')->count(),
            'sent' => $mine()->count(),
            'savedCount' => $this->saved->count($request),

            'livePosts' => JobSeekerPost::query()
                ->where('user_id', $user->id)
                ->published()
                ->count(),

            'pendingPosts' => JobSeekerPost::query()
                ->where('user_id', $user->id)
                ->where('status', JobSeekerPost::STATUS_PENDING)
                ->count(),

            'recentApplications' => $mine()
                ->with(['job.company', 'job.city'])
                ->latest()
                ->take(8)
                ->get(),

            'posts' => JobSeekerPost::query()
                ->where('user_id', $user->id)
                ->with(['city', 'category'])
                ->latest('created_at')
                ->take(5)
                ->get(),
        ]);
    }
}
