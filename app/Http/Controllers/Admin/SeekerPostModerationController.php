<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seeker\PostRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use App\Models\JobSeekerPost;
use App\Services\ListingModerator;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The other half of the queue: people advertising themselves.
 *
 * Deliberately the same shape as JobModerationController — the two go through
 * the same transitions and an admin should not have to learn two screens.
 */
class SeekerPostModerationController extends Controller
{
    public function __construct(private readonly ListingModerator $moderator) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', JobSeekerPost::STATUS_PENDING),
            'city' => $request->query('city', 'all'),
            'cat' => $request->query('cat', 'all'),
        ];

        $posts = JobSeekerPost::query()
            ->ofStatus($filters['status'])
            ->adminSearch($filters['q'])
            ->filtered($filters['city'], $filters['cat'])
            ->with(['city', 'category', 'user'])
            ->when(
                $filters['status'] === JobSeekerPost::STATUS_PENDING,
                fn ($q) => $q->oldest('created_at'),
                fn ($q) => $q->latest('created_at'),
            )
            ->paginate(25)
            ->withQueryString();

        return view('admin.seekers.index', [
            'posts' => $posts,
            'filters' => $filters,
            'cities' => City::active()->get(),
            'categories' => Category::active()->get(),
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    public function show(JobSeekerPost $post): View
    {
        $post->load(['city', 'category', 'employmentType', 'user', 'reviewer']);

        return view('admin.seekers.show', ['post' => $post]);
    }

    /**
     * Correct a post in place. Same reasoning as the listing side: a moderator
     * who can already approve or reject this post can certainly fix the city on
     * it, and a rejection round-trip for a typo helps nobody.
     */
    public function edit(JobSeekerPost $post): View
    {
        return view('seeker.posts.form', [
            'post' => $post,
            'cities' => City::active()->get(),
            'categories' => Category::active()->get(),
            'employmentTypes' => EmploymentType::active()->get(),
            'adminMode' => true,
            'action' => lroute('admin.seekers.update', $post),
            'formTitle' => __('admin.edit.seekerTitle'),
        ]);
    }

    public function update(PostRequest $request, JobSeekerPost $post): RedirectResponse
    {
        $post->fill($request->safe()->except('cv'));
        $post->transfer_available = $request->boolean('transfer_available');
        $post->available_immediately = $request->boolean('available_immediately');
        $post->whatsapp = Phone::normalise($request->input('whatsapp')) ?? $request->input('whatsapp');
        $post->phone = Phone::normalise($request->input('phone')) ?? $request->input('phone');

        if ($request->hasFile('cv')) {
            $post->cv_path = $request->file('cv')->store('cvs', 'local');
        }

        // Status and publish window left alone, exactly as on the listing side.
        $post->save();
        $this->moderator->refreshCounts();

        return redirect()
            ->to(lroute('admin.seekers.show', $post))
            ->with('status', __('admin.edit.seekerSaved'));
    }

    public function approve(Request $request, JobSeekerPost $post): RedirectResponse
    {
        $this->moderator->approve($post, $request->user());

        return back()->with('status', __('admin.flash.approved'));
    }

    public function reject(Request $request, JobSeekerPost $post): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->moderator->reject($post, $request->user(), $data['reason']);

        return back()->with('status', __('admin.flash.rejected'));
    }

    public function unpublish(Request $request, JobSeekerPost $post): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->moderator->unpublish($post, $request->user(), $data['reason'] ?? null);

        return back()->with('status', __('admin.flash.unpublished'));
    }

    public function destroy(JobSeekerPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->to(lroute('admin.seekers.index'))
            ->with('status', __('admin.flash.deleted'));
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'min:5', 'max:1000'],
        ]);

        $posts = JobSeekerPost::query()->whereIn('id', $data['ids'])->get();
        $admin = $request->user();

        foreach ($posts as $post) {
            match ($data['action']) {
                'approve' => $this->moderator->approve($post, $admin),
                'reject' => $this->moderator->reject($post, $admin, $data['reason']),
                'delete' => $post->delete(),
            };
        }

        return back()->with('status', __('admin.flash.bulk', ['n' => $posts->count()]));
    }

    /** @return array<string, int> */
    private function statusCounts(): array
    {
        $counts = JobSeekerPost::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counts['all'] = array_sum($counts);

        return $counts;
    }
}
