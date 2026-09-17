<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The blog, from the editor's side.
 *
 * Unlike the job and seeker queues this is authoring rather than moderation —
 * nothing arrives here from the public, so there is no approve/reject pair. The
 * two states that matter are draft and published, and the publish date doubles
 * as the scheduler.
 */
class BlogPostController extends Controller
{
    /** Where post images live on the public disk. */
    private const IMAGE_DIR = 'blog';

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', 'all'),
            'section' => $request->query('section', 'all'),
        ];

        $posts = BlogPost::query()
            ->with(['category', 'author'])
            ->adminSearch($filters['q'])
            ->ofStatus($filters['status'])
            ->inSection($filters['section'])
            // Drafts and scheduled posts are the editor's open work, so the
            // list is ordered by when it was touched, not when it went live.
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.blog.index', [
            'posts' => $posts,
            'filters' => $filters,
            'sections' => BlogCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.form', $this->formData(new BlogPost([
            'status' => BlogPost::STATUS_DRAFT,
            'is_indexable' => true,
        ])));
    }

    public function store(BlogPostRequest $request): RedirectResponse
    {
        $post = new BlogPost;

        $this->fill($post, $request);

        $post->user_id = $request->user()->id;
        $post->slug = $request->input('slug')
            ?: $this->uniqueSlug((string) $request->input('title_en'));

        $post->save();

        return redirect()
            ->to(lroute('admin.blog.edit', $post))
            ->with('status', __('admin.blog.flash.created'));
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.form', $this->formData($post));
    }

    public function update(BlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        $this->fill($post, $request);

        // An editor may correct the slug, but a blank box means "leave it" —
        // never "regenerate it", which would break a URL already in the wild.
        if ($request->filled('slug') && $request->input('slug') !== $post->slug) {
            $post->slug = $request->input('slug');
        }

        $post->save();

        return back()->with('status', __('admin.blog.flash.saved'));
    }

    /** Take a live post back to draft without losing its publish date. */
    public function unpublish(BlogPost $post): RedirectResponse
    {
        $post->forceFill(['status' => BlogPost::STATUS_DRAFT])->save();

        return back()->with('status', __('admin.blog.flash.unpublished'));
    }

    public function publish(BlogPost $post): RedirectResponse
    {
        $post->forceFill([
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => $post->published_at ?: now(),
        ])->save();

        return back()->with('status', __('admin.blog.flash.published'));
    }

    /**
     * Soft delete. The image is deliberately left on disk: the row can be
     * restored, and an orphaned file is cheaper than a restored post with a
     * broken hero.
     */
    public function destroy(BlogPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->to(lroute('admin.blog.index'))
            ->with('status', __('admin.blog.flash.deleted'));
    }

    /* ── Internals ──────────────────────────────────────────────────────── */

    private function fill(BlogPost $post, BlogPostRequest $request): void
    {
        $post->fill($request->safe()->except([
            'slug', 'image', 'remove_image', 'is_featured', 'is_indexable', 'published_at',
        ]));

        $post->fill($request->bodyParagraphs());

        // Unchecked boxes are absent from the payload, not false.
        $post->is_featured = $request->boolean('is_featured');
        $post->is_indexable = $request->boolean('is_indexable');

        // Publishing with no date set means "now"; an explicit future date
        // schedules it and the published scope holds it back until then.
        $post->published_at = $request->filled('published_at')
            ? $request->date('published_at')
            : ($post->published_at ?: ($request->input('status') === BlogPost::STATUS_PUBLISHED ? now() : null));

        $this->handleImage($post, $request);
    }

    private function handleImage(BlogPost $post, BlogPostRequest $request): void
    {
        if ($request->boolean('remove_image')) {
            $this->deleteImage($post);
            $post->image_path = null;

            return;
        }

        $file = $request->file('image');

        if (! $file instanceof UploadedFile) {
            return;
        }

        // Replacing swaps the file and removes the old one, so a post that has
        // been re-illustrated a dozen times leaves one file behind, not twelve.
        $this->deleteImage($post);

        $name = Str::random(40).'.'.$file->extension();
        $file->storeAs(self::IMAGE_DIR, $name, 'public');

        // Stored as a web path off the storage symlink so the views can call
        // asset() on it exactly like the job card art.
        $post->image_path = 'storage/'.self::IMAGE_DIR.'/'.$name;
    }

    private function deleteImage(BlogPost $post): void
    {
        if (! $post->image_path) {
            return;
        }

        Storage::disk('public')->delete(Str::after($post->image_path, 'storage/'));
    }

    /**
     * Slugs come off the English headline and must stay unique across the
     * table, soft-deleted rows included — a deleted article's URL should not
     * silently start resolving to a different one.
     */
    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $n = 1;

        while (BlogPost::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$n;
        }

        return $slug;
    }

    /** @return array<string, mixed> */
    private function formData(BlogPost $post): array
    {
        return [
            'post' => $post,
            'sections' => BlogCategory::active()->get(),
        ];
    }
}
