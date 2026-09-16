<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Models\JobSeekerPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The board's front page: the hero search, the category tiles, featured and
 * latest listings, the seeker feed, and the jobs-by-city rollup.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('public.home', [
            'categories' => Category::active()->get(),
            'cities' => City::active()->get(),
            'employmentTypes' => EmploymentType::active()->get(),

            'featured' => Job::query()
                ->published()
                ->featured()
                ->with(['company', 'city', 'category', 'employmentType'])
                ->latest('published_at')
                ->take(6)
                ->get(),

            'latest' => Job::query()
                ->published()
                ->with(['company', 'city', 'category', 'employmentType'])
                ->latest('published_at')
                ->take(12)
                ->get(),

            'seekerPosts' => JobSeekerPost::query()
                ->published()
                ->with(['city', 'category'])
                ->latest('published_at')
                ->take(8)
                ->get(),

            // The counters under the hero. Read off the cached per-row totals
            // rather than counting the listings table on every visit.
            'totalJobs' => (int) City::active()->sum('jobs_count'),
            'totalSeekers' => JobSeekerPost::query()->published()->count(),
        ]);
    }
}
