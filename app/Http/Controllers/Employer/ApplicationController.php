<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Applicants, across all of an employer's listings or within one of them.
 */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filters = [
            'job' => $request->query('job', 'all'),
            'status' => $request->query('status', 'all'),
        ];

        $applications = Application::query()
            // Scoped by the listing's owner, not by the application, so there
            // is no route into someone else's applicants.
            ->whereHas('job', fn ($q) => $q->where('user_id', $user->id))
            ->with(['user', 'job'])
            ->when($filters['job'] !== 'all', fn ($q) => $q->where('job_id', $filters['job']))
            ->when($filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('employer.applications.index', [
            'applications' => $applications,
            'filters' => $filters,
            'jobs' => Job::query()
                ->where('user_id', $user->id)
                ->orderBy('title_en')
                ->get(),
        ]);
    }

    /**
     * Opening an application marks it read, which is what clears the badge and
     * what the seeker's "usually reply within three days" promise is measured
     * against. Only the first open counts.
     */
    public function show(Request $request, Application $application): View
    {
        $this->authorise($request, $application);

        $application->load(['user', 'job.city', 'job.category']);

        if ($application->viewed_at === null) {
            $application->forceFill([
                'viewed_at' => now(),
                'status' => Application::STATUS_VIEWED,
            ])->save();
        }

        return view('employer.applications.show', ['application' => $application]);
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $this->authorise($request, $application);

        $data = $request->validate([
            'status' => ['required', 'in:viewed,shortlisted,rejected,hired'],
            'employer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->forceFill([
            'status' => $data['status'],
            'employer_note' => $data['employer_note'] ?? $application->employer_note,
            'viewed_at' => $application->viewed_at ?? now(),
        ])->save();

        return back()->with('status', __('employer.flash.applicationUpdated'));
    }

    private function authorise(Request $request, Application $application): void
    {
        $application->loadMissing('job');

        abort_unless($application->job?->user_id === $request->user()->id, 404);
    }
}
