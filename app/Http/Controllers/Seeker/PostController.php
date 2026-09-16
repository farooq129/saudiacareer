<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seeker\PostRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use App\Models\JobSeekerPost;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** A seeker's own "available for work" posts. */
class PostController extends Controller
{
    public function index(Request $request): View
    {
        return view('seeker.posts.index', [
            'posts' => JobSeekerPost::query()
                ->where('user_id', $request->user()->id)
                ->with(['city', 'category'])
                ->latest('created_at')
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $post = new JobSeekerPost([
            'display_name_ar' => $user->name,
            'display_name_en' => $user->name,
            'whatsapp' => $user->phone,
            'phone' => $user->phone,
            'email' => $user->email,
        ]);

        return view('seeker.posts.form', $this->formData($post));
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = new JobSeekerPost($request->safe()->except('cv'));

        $post->user_id = $request->user()->id;
        $post->slug = $this->uniqueSlug($request->input('display_name_en').'-'.$request->input('headline_en'));
        $post->transfer_available = $request->boolean('transfer_available');
        $post->available_immediately = $request->boolean('available_immediately');
        $post->whatsapp = Phone::normalise($request->input('whatsapp')) ?? $request->input('whatsapp');
        $post->phone = Phone::normalise($request->input('phone')) ?? $request->input('phone');

        if ($request->hasFile('cv')) {
            $post->cv_path = $request->file('cv')->store('cvs', 'local');
        }

        $this->applySubmitStatus($post, $request->input('intent'));
        $post->save();

        return redirect()
            ->to(lroute('seeker.posts.index'))
            ->with('status', $post->status === JobSeekerPost::STATUS_DRAFT
                ? __('seeker.flash.draftSaved')
                : __('seeker.flash.submitted'));
    }

    public function edit(Request $request, JobSeekerPost $post): View
    {
        $this->authorise($request, $post);

        return view('seeker.posts.form', $this->formData($post));
    }

    public function update(PostRequest $request, JobSeekerPost $post): RedirectResponse
    {
        $this->authorise($request, $post);

        $post->fill($request->safe()->except('cv'));
        $post->transfer_available = $request->boolean('transfer_available');
        $post->available_immediately = $request->boolean('available_immediately');
        $post->whatsapp = Phone::normalise($request->input('whatsapp')) ?? $request->input('whatsapp');
        $post->phone = Phone::normalise($request->input('phone')) ?? $request->input('phone');

        if ($request->hasFile('cv')) {
            $post->cv_path = $request->file('cv')->store('cvs', 'local');
        }

        // Same rule as an employer's listing: an edit to something already live
        // goes back through review.
        if (config('board.listing.moderated') && $post->status === JobSeekerPost::STATUS_PUBLISHED) {
            $post->status = JobSeekerPost::STATUS_PENDING;
            $post->published_at = null;
        } else {
            $this->applySubmitStatus($post, $request->input('intent'));
        }

        $post->save();

        return redirect()
            ->to(lroute('seeker.posts.index'))
            ->with('status', __('seeker.flash.updated'));
    }

    public function destroy(Request $request, JobSeekerPost $post): RedirectResponse
    {
        $this->authorise($request, $post);

        $post->delete();

        return redirect()
            ->to(lroute('seeker.posts.index'))
            ->with('status', __('seeker.flash.deleted'));
    }

    private function authorise(Request $request, JobSeekerPost $post): void
    {
        abort_unless($post->user_id === $request->user()->id, 404);
    }

    private function applySubmitStatus(JobSeekerPost $post, ?string $intent): void
    {
        if ($intent === 'draft') {
            $post->status = JobSeekerPost::STATUS_DRAFT;
            $post->published_at = null;

            return;
        }

        if (config('board.listing.moderated')) {
            $post->status = JobSeekerPost::STATUS_PENDING;
            $post->published_at = null;

            return;
        }

        $post->status = JobSeekerPost::STATUS_PUBLISHED;
        $post->published_at = now();
        $post->expires_at = now()->addDays((int) config('board.listing.lifetime_days'));
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'seeker';
        $slug = $base;
        $n = 1;

        while (JobSeekerPost::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$n;
        }

        return $slug;
    }

    private function formData(JobSeekerPost $post): array
    {
        return [
            'post' => $post,
            'cities' => City::active()->get(),
            'categories' => Category::active()->get(),
            'employmentTypes' => EmploymentType::active()->get(),
        ];
    }
}
