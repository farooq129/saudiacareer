<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\View\View;

/**
 * Every listing on the board, and the controls over it.
 */
class JobModerationController extends Controller
{
    public function __construct(private readonly ListingModerator $moderator) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status', Job::STATUS_PENDING),
            'city' => $request->query('city', 'all'),
            'cat' => $request->query('cat', 'all'),
        ];

        $jobs = Job::query()
            ->ofStatus($filters['status'])
            ->adminSearch($filters['q'])
            ->filtered($filters['city'], $filters['cat'], null)
            ->with(['company', 'city', 'category', 'employmentType', 'user'])
            ->withCount(['reports' => fn ($q) => $q->open()])
            // Pending first and oldest-first within it, so the list opens on
            // the work. Every other status reads newest-first, which is what
            // you want when you are looking something up rather than clearing
            // a queue.
            ->when(
                $filters['status'] === Job::STATUS_PENDING,
                fn ($q) => $q->oldest('created_at'),
                fn ($q) => $q->latest('created_at'),
            )
            ->paginate(25)
            ->withQueryString();

        return view('admin.jobs.index', [
            'jobs' => $jobs,
            'filters' => $filters,
            'cities' => City::active()->get(),
            'categories' => Category::active()->get(),
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    /** Everything about one listing, in both languages, on one screen. */
    public function show(Job $job): View
    {
        $job->load([
            'company.city', 'city', 'category', 'employmentType',
            'user', 'reviewer',
            'reports' => fn ($q) => $q->with(['user', 'resolver'])->latest(),
            'applications' => fn ($q) => $q->with('user')->latest()->take(10),
        ]);

        return view('admin.jobs.show', ['job' => $job]);
    }

    /**
     * Correct a listing in place.
     *
     * Open to moderators as well as admins: fixing a mangled title or a
     * miscategorised ad is the same job as approving one, and forcing a
     * rejection round-trip for a typo costs the employer a day and the board a
     * listing. The employer's own form is reused rather than copied — see the
     * note at the top of that view.
     */
    public function edit(Job $job): View
    {
        return view('employer.jobs.form', [
            'job' => $job,
            'cities' => City::active()->get(),
            'categories' => Category::active()->get(),
            'employmentTypes' => EmploymentType::active()->get(),
            'adminMode' => true,
            'action' => lroute('admin.jobs.update', $job),
            'formTitle' => __('admin.edit.jobTitle'),
        ]);
    }

    public function update(JobRequest $request, Job $job): RedirectResponse
    {
        $job->fill($request->safe()->except(array_keys($request->listFields())));
        $job->fill($request->listFields());
        $job->transfer_available = $request->boolean('transfer_available');
        $job->whatsapp = Phone::normalise($request->input('whatsapp')) ?? $request->input('whatsapp');
        $job->phone = Phone::normalise($request->input('phone')) ?? $request->input('phone');

        /*
         * The status and the publish window are deliberately untouched. An
         * employer editing a live ad sends it back for review, because they
         * could otherwise rewrite an approved ad into anything. Staff are the
         * people that rule protects against, so the same bounce here would only
         * unpublish a listing a moderator just fixed. Changing state stays on
         * the review screen, where the reason box and the audit trail are.
         */
        $job->save();
        $this->moderator->refreshCounts();

        return redirect()
            ->to(lroute('admin.jobs.show', $job))
            ->with('status', __('admin.edit.jobSaved'));
    }

    public function approve(Request $request, Job $job): RedirectResponse
    {
        $this->moderator->approve($job, $request->user());

        return back()->with('status', __('admin.flash.approved'));
    }

    public function reject(Request $request, Job $job): RedirectResponse
    {
        // The reason is required because it is shown back to the employer. A
        // rejection with no reason only produces a support ticket.
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->moderator->reject($job, $request->user(), $data['reason']);

        return back()->with('status', __('admin.flash.rejected'));
    }

    public function unpublish(Request $request, Job $job): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->moderator->unpublish($job, $request->user(), $data['reason'] ?? null);

        return back()->with('status', __('admin.flash.unpublished'));
    }

    /**
     * The two promotion flags. Kept apart from the approve/reject transitions
     * because they are reversible presentation choices, not a verdict on the
     * listing, and they must not touch the publish window.
     */
    public function flags(Request $request, Job $job): RedirectResponse
    {
        $data = $request->validate([
            'flag' => ['required', 'in:featured,urgent'],
        ]);

        $column = $data['flag'] === 'featured' ? 'is_featured' : 'is_urgent';

        $job->forceFill([$column => ! $job->{$column}])->save();

        return back()->with('status', __('admin.flash.updated'));
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        // Soft delete: the listing leaves the board but its applications and
        // any reports against it keep pointing at something real.
        $job->delete();

        $this->moderator->refreshCounts();

        return redirect()
            ->to(lroute('admin.jobs.index'))
            ->with('status', __('admin.flash.deleted'));
    }

    /**
     * Clear several listings at once. Approving in bulk is the common case:
     * a morning's queue is mostly ordinary ads from employers already known.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            // Only required for a bulk rejection, for the same reason as above.
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'min:5', 'max:1000'],
        ]);

        $jobs = Job::query()->whereIn('id', $data['ids'])->get();
        $admin = $request->user();

        foreach ($jobs as $job) {
            match ($data['action']) {
                'approve' => $this->moderator->approve($job, $admin),
                'reject' => $this->moderator->reject($job, $admin, $data['reason']),
                'delete' => $job->delete(),
            };
        }

        $this->moderator->refreshCounts();

        return back()->with('status', __('admin.flash.bulk', ['n' => $jobs->count()]));
    }

    /** @return array<string, int> */
    private function statusCounts(): array
    {
        $counts = Job::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counts['all'] = array_sum($counts);

        return $counts;
    }
}
