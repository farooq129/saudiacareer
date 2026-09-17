<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Support\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    /** The article feed, optionally narrowed to one section or a keyword. */
    public function index(Request $request, ?BlogCategory $blogCategory = null): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'section' => $blogCategory?->key ?? $request->query('section', 'all'),
        ];

        $posts = BlogPost::query()
            ->published()
            ->search($filters['q'])
            ->inSection($filters['section'])
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        /*
         * The lead article only earns the top slot on an unfiltered first page.
         * Promoting one into a filtered result set would push down the very
         * thing the reader asked for, and it is excluded from the grid below so
         * it never appears twice.
         */
        $lead = null;

        if ($filters['q'] === '' && $filters['section'] === 'all' && $posts->currentPage() === 1) {
            $lead = BlogPost::query()
                ->published()
                ->featured()
                ->with(['category', 'author'])
                ->latest('published_at')
                ->first();
        }

        $heading = $blogCategory?->name ?? __('blog.title');
        $blurb = $blogCategory?->blurb ?? __('blog.intro');

        return view('public.blog.index', [
            'heading' => $heading,
            'blurb' => $blurb,
            'jsonLd' => StructuredData::blog($heading, $blurb, url()->current(), $posts->getCollection()),
            'posts' => $posts,
            'lead' => $lead,
            'sections' => BlogCategory::active()->withCount(['posts' => fn ($q) => $q->published()])->get(),
            'section' => $blogCategory,
            'filters' => $filters,
        ]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->isPublished(), 404);

        $post->load(['category', 'author']);

        // Fire-and-forget counter; no need to reload the model for it.
        $post->newQuery()->whereKey($post->getKey())->increment('views_count');

        /*
         * Related reading, same section first. Falls back to the newest
         * articles when a section is too thin to fill the rail, so the foot of
         * an article is never a dead end.
         */
        $related = BlogPost::query()
            ->published()
            ->whereKeyNot($post->getKey())
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->with('category')
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($related->count() < 3) {
            $related = $related->concat(
                BlogPost::query()
                    ->published()
                    ->whereKeyNot($post->getKey())
                    ->whereNotIn('id', $related->pluck('id'))
                    ->with('category')
                    ->latest('published_at')
                    ->take(3 - $related->count())
                    ->get()
            );
        }

        $image = $post->image_path ? asset($post->image_path) : asset('images/main_logo.png');

        $crumbs = [
            [__('detail.home'), lroute('home')],
            [__('blog.title'), lroute('blog.index')],
        ];

        if ($post->category) {
            $crumbs[] = [$post->category->name, lroute('blog.section', $post->category)];
        }

        $crumbs[] = [$post->title, null];

        return view('public.blog.show', [
            'post' => $post,
            'related' => $related,
            'image' => $image,
            'jsonLd' => StructuredData::blogPosting($post, $image, url()->current()),
            'breadcrumbsLd' => StructuredData::breadcrumbs($crumbs),
        ]);
    }
}
