<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobReport;
use App\Services\ListingModerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reports raised against listings.
 *
 * Upholding a report takes the listing down in the same action: an admin who
 * has just decided an ad is a fee scam should not have to navigate somewhere
 * else to act on that decision, because that is the step that gets skipped
 * when the queue is long.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ListingModerator $moderator) {}

    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->query('status', 'open'),
            'reason' => $request->query('reason', 'all'),
        ];

        $reports = JobReport::query()
            ->with(['job.company', 'job.city', 'user', 'resolver'])
            ->when($filters['status'] === 'open', fn ($q) => $q->open())
            ->when(
                ! in_array($filters['status'], ['open', 'all'], true),
                fn ($q) => $q->where('status', $filters['status']),
            )
            ->when($filters['reason'] !== 'all', fn ($q) => $q->where('reason', $filters['reason']))
            // Oldest first: a report that has sat for a week is the one that
            // matters, not the one that arrived five minutes ago.
            ->oldest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'filters' => $filters,
            'openCount' => JobReport::query()->open()->count(),
        ]);
    }

    /**
     * Uphold a report: the listing comes off the board and the reason given to
     * the employer is the admin's note, not the reporter's wording.
     */
    public function uphold(Request $request, JobReport $report): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $report->loadMissing('job');

        if ($report->job) {
            $this->moderator->reject($report->job, $request->user(), $data['note']);
        }

        $report->forceFill([
            'status' => JobReport::STATUS_UPHELD,
            'resolution_note' => $data['note'],
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ])->save();

        return back()->with('status', __('admin.flash.reportUpheld'));
    }

    /** Turn a report down. The listing is untouched. */
    public function dismiss(Request $request, JobReport $report): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->forceFill([
            'status' => JobReport::STATUS_DISMISSED,
            'resolution_note' => $data['note'] ?? null,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ])->save();

        return back()->with('status', __('admin.flash.reportDismissed'));
    }
}
