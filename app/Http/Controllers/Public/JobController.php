<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Models\JobReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    /** The results screen: keyword plus the three taxonomy filters. */
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'city' => $request->query('city', 'all'),
            'cat' => $request->query('cat', 'all'),
            'type' => $request->query('type', 'all'),
        ];

        $jobs = Job::query()
            ->published()
            ->search($filters['q'])
            ->filtered($filters['city'], $filters['cat'], $filters['type'])
            ->with(['company', 'city', 'category', 'employmentType'])
            // Featured ads sit at the top of their own result set, but never
            // ahead of a closer keyword match — ordering by date within each
            // band keeps the feed honest.
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::active()->get();
        $cities = City::active()->get();

        return view('public.results', [
            'jobs' => $jobs,
            'filters' => $filters,
            'categories' => $categories,
            'cities' => $cities,
            'employmentTypes' => EmploymentType::active()->get(),

            // The heading names what you filtered by, so it needs the rows
            // behind the two keys rather than the keys themselves. Read off the
            // lists already loaded for the filter rail — no extra query.
            'activeCategory' => $categories->firstWhere('key', $filters['cat']),
            'activeCity' => $cities->firstWhere('key', $filters['city']),
        ]);
    }

    public function show(Request $request, Job $job): View
    {
        abort_unless($job->isPublished(), 404);

        $job->load(['company.city', 'city', 'category', 'employmentType']);

        // Fire-and-forget counter; no need to reload the model for it.
        $job->newQuery()->whereKey($job->getKey())->increment('views_count');

        return view('public.show', [
            'job' => $job,
            'similar' => Job::query()
                ->published()
                ->whereKeyNot($job->getKey())
                ->where('category_id', $job->category_id)
                ->with(['company', 'city', 'category', 'employmentType'])
                ->latest('published_at')
                ->take(4)
                ->get(),
        ]);
    }

    /**
     * Report a listing. Open to signed-out visitors, because the ads most worth
     * reporting — fee scams, visa sales — are the ones a visitor meets before
     * they ever make an account.
     */
    public function report(Request $request, Job $job): RedirectResponse
    {
        abort_unless($job->isPublished(), 404);

        $data = $request->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', JobReport::REASONS)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $job->reports()->create([
            'user_id' => $request->user()?->id,
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', __('Thank you. Our team will review this ad.'));
    }
}
