<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\JobRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Services\ListingModerator;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * An employer's own vacancies.
 *
 * Every query here is scoped to the signed-in employer, and every route-model
 * binding is checked against them — see authorise(). A board where guessing a
 * slug shows you somebody else's applicant list is not a board anyone will use
 * twice.
 */
class JobController extends Controller
{
    public function __construct(private readonly ListingModerator $moderator) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        return view('employer.jobs.index', [
            'jobs' => Job::query()
                ->where('user_id', $request->user()->id)
                ->ofStatus($status)
                ->with(['city', 'category', 'employmentType'])
                ->withCount('applications')
                ->latest('created_at')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'statusCounts' => $this->statusCounts($request),
        ]);
    }

    public function create(Request $request): View
    {
        $company = $request->user()->company;

        // A new listing starts pre-filled with the employer's own contact
        // details, because that is what it will be nine times out of ten.
        $job = new Job([
            'whatsapp' => $company?->whatsapp,
            'phone' => $company?->phone ?? $request->user()->phone,
            'email' => $company?->email ?? $request->user()->email,
        ]);

        return view('employer.jobs.form', $this->formData($job));
    }

    public function store(JobRequest $request): RedirectResponse
    {
        $job = new Job($request->safe()->except(array_keys($request->listFields())));

        $job->fill($request->listFields());
        $job->user_id = $request->user()->id;
        $job->company_id = $request->user()->company?->id;
        $job->slug = $this->uniqueSlug($request->input('title_en'));
        $job->transfer_available = $request->boolean('transfer_available');
        $job->whatsapp = Phone::normalise($request->input('whatsapp')) ?? $request->input('whatsapp');
        $job->phone = Phone::normalise($request->input('phone')) ?? $request->input('phone');

        $this->applySubmitStatus($job, $request->input('intent'));
        $job->save();

        return redirect()
            ->to(lroute('employer.jobs.index'))
            ->with('status', $job->status === Job::STATUS_DRAFT
                ? __('employer.flash.draftSaved')
                : __('employer.flash.submitted'));
    }

    public function edit(Request $request, Job $job): View
    {
        $this->authorise($request, $job);

        return view('employer.jobs.form', $this->formData($job));
    }

    public function update(JobRequest $request, Job $job): RedirectResponse
    {
        $this->authorise($request, $job);

        $job->fill($request->safe()->except(array_keys($request->listFields())));
        $job->fill($request->listFields());
        $job->transfer_available = $request->boolean('transfer_available');
        $job->whatsapp = Phone::normalise($request->input('whatsapp')) ?? $request->input('whatsapp');
        $job->phone = Phone::normalise($request->input('phone')) ?? $request->input('phone');

        /*
         * Editing a live listing sends it back for review when moderation is on.
         * Otherwise an employer could get an innocuous ad approved and then
         * rewrite it into whatever they liked — which is exactly the hole a
         * free board gets probed for.
         */
        if (config('board.listing.moderated') && $job->status === Job::STATUS_PUBLISHED) {
            $job->status = Job::STATUS_PENDING;
            $job->published_at = null;
        } else {
            $this->applySubmitStatus($job, $request->input('intent'));
        }

        $job->save();
        $this->moderator->refreshCounts();

        return redirect()
            ->to(lroute('employer.jobs.index'))
            ->with('status', __('employer.flash.updated'));
    }

    /** Close a vacancy that has been filled. */
    public function close(Request $request, Job $job): RedirectResponse
    {
        $this->authorise($request, $job);

        $this->moderator->close($job, $request->user());

        return back()->with('status', __('employer.flash.closed'));
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        $this->authorise($request, $job);

        $job->delete();
        $this->moderator->refreshCounts();

        return redirect()
            ->to(lroute('employer.jobs.index'))
            ->with('status', __('employer.flash.deleted'));
    }

    /** A listing belongs to the employer looking at it, or it is a 404. */
    private function authorise(Request $request, Job $job): void
    {
        abort_unless($job->user_id === $request->user()->id, 404);
    }

    /**
     * "Save as draft" keeps it private; "submit" puts it in the queue, or
     * publishes it outright when moderation is switched off.
     */
    private function applySubmitStatus(Job $job, ?string $intent): void
    {
        if ($intent === 'draft') {
            $job->status = Job::STATUS_DRAFT;
            $job->published_at = null;

            return;
        }

        if (config('board.listing.moderated')) {
            $job->status = Job::STATUS_PENDING;
            $job->published_at = null;

            return;
        }

        $job->status = Job::STATUS_PUBLISHED;
        $job->published_at = now();
        $job->expires_at = now()->addDays((int) config('board.listing.lifetime_days'));
    }

    /**
     * Slugs come off the English title and must stay unique across the table,
     * including against soft-deleted rows — a deleted listing's URL should not
     * silently start resolving to a different vacancy.
     */
    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $base;
        $n = 1;

        while (Job::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$n;
        }

        return $slug;
    }

    private function formData(Job $job): array
    {
        return [
            'job' => $job,
            'cities' => City::active()->get(),
            'categories' => Category::active()->get(),
            'employmentTypes' => EmploymentType::active()->get(),
        ];
    }

    /** @return array<string, int> */
    private function statusCounts(Request $request): array
    {
        $counts = Job::query()
            ->where('user_id', $request->user()->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counts['all'] = array_sum($counts);

        return $counts;
    }
}
