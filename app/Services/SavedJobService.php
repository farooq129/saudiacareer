<?php

namespace App\Services;

use App\Models\Job;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Saved ads, for signed-in and signed-out visitors alike.
 *
 * A guest's list is a plain array of listing ids in the session. On sign-in it
 * is merged into the account's rows and the session copy is dropped — see
 * mergeIntoAccount(), which the login flow calls.
 *
 * Merging has to be idempotent: a visitor who saves an ad, signs in, signs out
 * and saves it again must not end up with two rows or an integrity error.
 */
class SavedJobService
{
    public const SESSION_KEY = 'saved_jobs';

    /** The listings on the visitor's saved list, newest save first. */
    public function list(Request $request): Collection
    {
        $user = $request->user();

        if ($user) {
            return Job::query()
                ->published()
                ->whereIn('id', SavedJob::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->pluck('job_id'))
                ->with(['company', 'city', 'category', 'employmentType'])
                ->get();
        }

        $ids = $this->sessionIds($request);

        if ($ids === []) {
            return collect();
        }

        return Job::query()
            ->published()
            ->whereIn('id', $ids)
            ->with(['company', 'city', 'category', 'employmentType'])
            ->get();
    }

    public function add(Request $request, Job $job): void
    {
        $user = $request->user();

        if ($user) {
            SavedJob::query()->firstOrCreate([
                'user_id' => $user->id,
                'job_id' => $job->id,
            ]);

            return;
        }

        $ids = $this->sessionIds($request);
        $ids[] = $job->id;

        $request->session()->put(self::SESSION_KEY, array_values(array_unique($ids)));
    }

    public function remove(Request $request, Job $job): void
    {
        $user = $request->user();

        if ($user) {
            SavedJob::query()
                ->where('user_id', $user->id)
                ->where('job_id', $job->id)
                ->delete();

            return;
        }

        $ids = array_values(array_diff($this->sessionIds($request), [$job->id]));

        $request->session()->put(self::SESSION_KEY, $ids);
    }

    public function has(Request $request, Job $job): bool
    {
        $user = $request->user();

        if ($user) {
            return SavedJob::query()
                ->where('user_id', $user->id)
                ->where('job_id', $job->id)
                ->exists();
        }

        return in_array($job->id, $this->sessionIds($request), true);
    }

    public function count(Request $request): int
    {
        $user = $request->user();

        return $user
            ? SavedJob::query()->where('user_id', $user->id)->count()
            : count($this->sessionIds($request));
    }

    /**
     * Fold a guest's session list into their account. Called right after a
     * successful sign-in, before the session list is cleared.
     */
    public function mergeIntoAccount(Request $request, User $user): void
    {
        $ids = $this->sessionIds($request);

        if ($ids === []) {
            return;
        }

        // Only ids that still point at a live listing, so a stale session from
        // months ago cannot fail the insert on a deleted job.
        $live = Job::query()->whereIn('id', $ids)->pluck('id');

        foreach ($live as $id) {
            SavedJob::query()->firstOrCreate(['user_id' => $user->id, 'job_id' => $id]);
        }

        $request->session()->forget(self::SESSION_KEY);
    }

    /** @return list<int> */
    private function sessionIds(Request $request): array
    {
        // A request can reach the chrome without a session — an error page
        // rendered outside the web group, or a console-driven render. An empty
        // saved list is the right answer there, not an exception.
        if (! $request->hasSession()) {
            return [];
        }

        $ids = $request->session()->get(self::SESSION_KEY, []);

        return is_array($ids) ? array_values(array_filter(array_map('intval', $ids))) : [];
    }
}
