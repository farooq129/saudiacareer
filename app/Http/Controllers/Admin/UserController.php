<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Accounts.
 *
 * Suspending, not deleting, is the tool here: a deleted account takes its
 * listings and applications with it, and an employer who turns out to be a
 * scammer is exactly the account whose history you most want to keep.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'role' => $request->query('role', 'all'),
            'active' => $request->query('active', 'all'),
        ];

        $users = User::query()
            ->with('company')
            ->withCount(['jobs', 'seekerPosts', 'applications'])
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';

                $q->where(fn ($w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($filters['role'] !== 'all', fn ($q) => $q->where('role', $filters['role']))
            ->when($filters['active'] === 'yes', fn ($q) => $q->where('is_active', true))
            ->when($filters['active'] === 'no', fn ($q) => $q->where('is_active', false))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    /**
     * Suspend or restore an account.
     *
     * An admin cannot suspend themselves, which would lock them out of the
     * screen they are standing on, and cannot suspend another admin — removing
     * a colleague's access is a decision that belongs in a conversation, not in
     * a row action.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['access' => __('admin.users.cannotSuspendSelf')]);
        }

        if ($user->isAdmin()) {
            return back()->withErrors(['access' => __('admin.users.cannotSuspendAdmin')]);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('status', $user->is_active
            ? __('admin.flash.userRestored')
            : __('admin.flash.userSuspended'));
    }
}
