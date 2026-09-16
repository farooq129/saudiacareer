<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobSeekerPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Approving, rejecting and withdrawing listings.
 *
 * Jobs and seeker posts are moderated identically — the two tables carry the
 * same status column, the same review columns and the same publish window — so
 * one service handles both rather than two controllers growing their own
 * slightly different versions of the transition.
 *
 * Every transition records who did it and when. On a free board the verified
 * badge and the moderation queue are the only things standing between a seeker
 * and a recruitment-fee scam, so "who approved this" has to be answerable
 * months later, including for a listing that has since been deleted.
 */
class ListingModerator
{
    /**
     * Publish a listing.
     *
     * The publish window opens now rather than at whatever draft date the
     * poster set: a listing approved on Thursday should run for its full
     * lifetime from Thursday, not expire early because it was written a
     * fortnight ago. An already-published listing keeps its original date, so
     * re-approving after an edit does not push it back to the top of the feed.
     */
    public function approve(Model $listing, User $admin): void
    {
        $alreadyLive = $listing->status === Job::STATUS_PUBLISHED && $listing->published_at !== null;

        $publishedAt = $alreadyLive ? $listing->published_at : now();

        $listing->forceFill([
            'status' => Job::STATUS_PUBLISHED,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addDays((int) config('board.listing.lifetime_days')),
            'rejection_reason' => null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        $this->refreshCounts();
    }

    /**
     * Turn a listing down. The reason is required and is shown back to the
     * poster — a rejection with no reason just produces a support ticket.
     */
    public function reject(Model $listing, User $admin, string $reason): void
    {
        $listing->forceFill([
            'status' => Job::STATUS_REJECTED,
            'published_at' => null,
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        $this->refreshCounts();
    }

    /**
     * Pull a live listing back off the board without rejecting it — used when
     * an upheld report needs the ad down now and the conversation with the
     * employer can follow.
     */
    public function unpublish(Model $listing, User $admin, ?string $reason = null): void
    {
        $listing->forceFill([
            'status' => Job::STATUS_PENDING,
            'published_at' => null,
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        $this->refreshCounts();
    }

    /** Mark a vacancy as filled, or a seeker as hired. The happy ending. */
    public function close(Model $listing, User $admin): void
    {
        $listing->forceFill([
            'status' => $listing instanceof JobSeekerPost
                ? JobSeekerPost::STATUS_HIRED
                : Job::STATUS_FILLED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        $this->refreshCounts();
    }

    /**
     * Recompute the cached per-row listing totals.
     *
     * These feed the home page's city rollup and the category tiles, which are
     * read on every visit and must not run three COUNT queries each time. Three
     * correlated updates is cheap at this size and is simpler to reason about
     * than incrementing and decrementing on every transition — a counter that
     * drifts is worse than one recomputed on a write nobody is waiting on.
     */
    public function refreshCounts(): void
    {
        $live = "jobs.status = 'published' and jobs.deleted_at is null";

        DB::table('cities')->update([
            'jobs_count' => DB::raw("(select count(*) from jobs where jobs.city_id = cities.id and {$live})"),
        ]);

        DB::table('categories')->update([
            'jobs_count' => DB::raw("(select count(*) from jobs where jobs.category_id = categories.id and {$live})"),
        ]);

        DB::table('companies')->update([
            'jobs_count' => DB::raw("(select count(*) from jobs where jobs.company_id = companies.id and {$live})"),
        ]);
    }
}
