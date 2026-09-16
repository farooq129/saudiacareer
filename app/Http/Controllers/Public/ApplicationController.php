<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Applying to a listing from its page.
 *
 * Signing in is required, unlike saving an ad: an application carries a CV and
 * a name to an employer, and an employer needs to be able to reply to a real
 * account rather than to a session.
 */
class ApplicationController extends Controller
{
    public function store(Request $request, Job $job): RedirectResponse
    {
        abort_unless($job->isPublished(), 404);

        $data = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:4000'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
        ]);

        // One application per seeker per listing — the unique index says so
        // too, but saying it here gives a message instead of a 500.
        $existing = Application::query()
            ->where('job_id', $job->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return back()->with('status', __('apply.already'));
        }

        $application = new Application([
            'job_id' => $job->id,
            'user_id' => $request->user()->id,
            'source' => 'site',
            'cover_letter' => $data['cover_letter'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        if ($request->hasFile('cv')) {
            // The CV is copied onto the application rather than referenced from
            // the seeker's profile: the employer must keep seeing the CV as it
            // was sent, even after the seeker uploads a new one.
            $application->cv_path = $request->file('cv')->store('cvs', 'local');
        }

        $application->save();

        // Only on-site applications are counted. A WhatsApp tap is recorded
        // too but would flatter the listing if it landed in the same total.
        $job->newQuery()->whereKey($job->getKey())->increment('applications_count');

        return back()->with('status', __('apply.sent', ['company' => $job->company?->name ?? '']));
    }
}
