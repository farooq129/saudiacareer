<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobReport;
use App\Models\JobSeekerPost;
use App\Models\User;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/**
 * The sidebar for whichever signed-in area the user is in.
 *
 * All three areas share one layout, so the thing that actually distinguishes an
 * admin's view from an employer's is this list. Keeping it in one class means
 * the answer to "what can this role reach" is readable in one place rather than
 * spread across three sets of Blade conditionals.
 *
 * Badges are backlogs — work waiting on the person looking at the screen, never
 * a decorative total. An employer's badge counts applications they have not
 * opened; an admin's counts listings nobody has reviewed.
 */
class PortalNavigator
{
    /** @return array{title: string, items: list<array<string, mixed>>} */
    public function for(User $user): array
    {
        return match ($user->role) {
            User::ROLE_ADMIN, User::ROLE_MODERATOR => [
                // A moderator sees the same area under its own name, so it is
                // obvious from the first screen that this is not a full admin.
                'title' => $user->isAdmin() ? __('admin.title') : __('admin.moderatorTitle'),
                'items' => $this->adminItems($user),
            ],
            User::ROLE_EMPLOYER => [
                'title' => __('employer.title'),
                'items' => $this->employerItems($user),
            ],
            default => [
                'title' => __('seeker.title'),
                'items' => $this->seekerItems($user),
            ],
        };
    }

    /** @return list<array<string, mixed>> */
    private function adminItems(User $user): array
    {
        $rows = [
            ['admin.dashboard', 'ph-squares-four', __('admin.nav.dashboard'), null],
            ['admin.jobs.index', 'ph-briefcase', __('admin.nav.jobs'),
                Job::query()->awaitingReview()->count()],
            ['admin.seekers.index', 'ph-users-three', __('admin.nav.seekers'),
                JobSeekerPost::query()->awaitingReview()->count()],
            ['admin.reports.index', 'ph-flag', __('admin.nav.reports'),
                JobReport::query()->open()->count()],
            // Authoring, not a queue — nothing arrives here from the public,
            // so there is no backlog to badge.
            ['admin.blog.index', 'ph-newspaper', __('admin.nav.blog'), null],
            // A moderator can read the employer directory but not act on it, so
            // the badge counting employers awaiting verification is not their
            // backlog and is left off.
            ['admin.companies.index', 'ph-buildings', __('admin.nav.companies'),
                $user->isAdmin() ? Company::query()->where('is_verified', false)->count() : null],
        ];

        if ($user->isAdmin()) {
            $rows[] = ['admin.users.index', 'ph-users', __('admin.nav.users'), null];
            $rows[] = ['admin.staff.index', 'ph-shield-star', __('admin.nav.staff'), null];
        }

        // Last, and for everyone: your own name and password.
        $rows[] = ['admin.account.edit', 'ph-user-circle', __('admin.nav.account'), null];

        return $this->build($rows);
    }

    /** @return list<array<string, mixed>> */
    private function employerItems(User $user): array
    {
        // Applications the employer has not opened yet — the one number on the
        // screen that means someone is waiting on them.
        $unread = Application::query()
            ->whereNull('viewed_at')
            ->whereHas('job', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        return $this->build([
            ['employer.dashboard', 'ph-squares-four', __('employer.nav.dashboard'), null],
            ['employer.jobs.index', 'ph-briefcase', __('employer.nav.jobs'), null],
            ['employer.applications.index', 'ph-paper-plane-tilt', __('employer.nav.applications'), $unread],
            ['employer.company.edit', 'ph-buildings', __('employer.nav.company'), null],
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function seekerItems(User $user): array
    {
        return $this->build([
            ['seeker.dashboard', 'ph-squares-four', __('seeker.nav.dashboard'), null],
            ['seeker.posts.index', 'ph-user-list', __('seeker.nav.posts'), null],
            ['seeker.applications.index', 'ph-paper-plane-tilt', __('seeker.nav.applications'), null],
            ['saved.index', 'ph-bookmark-simple', __('nav.saved'), null],
            ['seeker.profile.edit', 'ph-user-circle', __('seeker.nav.profile'), null],
        ]);
    }

    /**
     * Turn the bare definitions into links, dropping any whose route does not
     * exist yet so an area can be built out a screen at a time without the
     * sidebar throwing.
     *
     * @param  list<array{0: string, 1: string, 2: string, 3: int|null}>  $rows
     * @return list<array<string, mixed>>
     */
    private function build(array $rows): array
    {
        $items = [];

        foreach ($rows as [$route, $icon, $label, $badge]) {
            if (! Route::has(Locale::current().'.'.$route)) {
                continue;
            }

            $items[] = [
                'url' => lroute($route),
                'icon' => $icon,
                'label' => $label,
                'badge' => $badge ?: null,
                'active' => $this->isActive($route),
            ];
        }

        return $items;
    }

    /**
     * A section stays lit while you are anywhere inside it — on a listing's
     * edit form as well as on the list it came from.
     */
    private function isActive(string $route): bool
    {
        if (request()->routeIs('*.'.$route)) {
            return true;
        }

        // 'employer.jobs.index' also lights on 'employer.jobs.*'.
        if (str_ends_with($route, '.index')) {
            return request()->routeIs('*.'.substr($route, 0, -5).'*');
        }

        return false;
    }
}
