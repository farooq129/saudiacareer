<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\JobSeekerPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The seeker feed — the half of the board employers browse. */
class SeekerPostController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'city' => $request->query('city', 'all'),
            'cat' => $request->query('cat', 'all'),
        ];

        return view('public.seekers.index', [
            'posts' => JobSeekerPost::query()
                ->published()
                ->filtered($filters['city'], $filters['cat'])
                ->with(['city', 'category'])
                ->latest('published_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'categories' => Category::active()->get(),
            'cities' => City::active()->get(),
        ]);
    }

    public function show(JobSeekerPost $post): View
    {
        abort_unless($post->status === JobSeekerPost::STATUS_PUBLISHED, 404);

        // `user` is needed for the contact panel, which falls back to the
        // account behind the post when the seeker left those fields empty.
        $post->load(['city', 'category', 'employmentType', 'user']);
        $post->newQuery()->whereKey($post->getKey())->increment('views_count');

        return view('public.seekers.show', ['post' => $post]);
    }
}
