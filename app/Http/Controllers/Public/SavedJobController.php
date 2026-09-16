<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\SavedJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "الإعلانات المحفوظة / Saved ads".
 *
 * Saving works signed out: asking a visitor to make an account before they can
 * keep a listing they are still deciding about loses both of them. The session
 * list is merged into the account on sign-in — see SavedJobService.
 */
class SavedJobController extends Controller
{
    public function __construct(private readonly SavedJobService $saved) {}

    public function index(Request $request): View
    {
        return view('public.saved', [
            'jobs' => $this->saved->list($request),
        ]);
    }

    public function store(Request $request, Job $job): RedirectResponse
    {
        abort_unless($job->isPublished(), 404);

        $this->saved->add($request, $job);

        return back()->with('status', __('Ad saved.'));
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        $this->saved->remove($request, $job);

        return back()->with('status', __('Ad removed from your saved list.'));
    }
}
