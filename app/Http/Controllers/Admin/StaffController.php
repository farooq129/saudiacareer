<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Who works here.
 *
 * Admin-only, and hedged in four ways, because every guard here exists to stop
 * a board ending up with nobody able to administer it:
 *
 *   - you cannot change your own role (no self-promotion, no self-demotion
 *     into a lockout)
 *   - you cannot delete or suspend yourself
 *   - the last remaining admin cannot be demoted or removed
 *   - a new staff account is created with a password you type, not one the
 *     system invents and emails
 */
class StaffController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.staff.index', [
            'staff' => User::query()
                ->whereIn('role', User::STAFF_ROLES)
                ->orderByRaw("role = 'admin' desc")
                ->orderBy('name')
                ->get(),
            'adminCount' => $this->adminCount(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(User::STAFF_ROLES)],
            // Longer than the public minimum: these accounts can take the whole
            // board down, and there are only ever a handful of them.
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $phone = Phone::normalise($data['phone']);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => __('Please enter a valid Saudi mobile number.'),
            ]);
        }

        if (User::query()->withTrashed()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => __('An account already exists for this number.'),
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'],
            'locale' => app()->getLocale(),
        ]);

        // A staff account is created by someone who has already checked who
        // they are, so the number counts as verified.
        $user->forceFill(['phone_verified_at' => now()])->save();
        $user->syncRoles([$data['role']]);

        return back()->with('status', __('admin.staff.created', ['name' => $user->name]));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->guardStaff($user);

        $data = $request->validate([
            'role' => ['required', Rule::in(User::STAFF_ROLES)],
        ]);

        if ($user->is($request->user())) {
            return back()->withErrors(['access' => __('admin.staff.cannotChangeSelf')]);
        }

        if ($this->wouldLeaveNoAdmin($user, $data['role'])) {
            return back()->withErrors(['access' => __('admin.staff.lastAdmin')]);
        }

        $user->forceFill(['role' => $data['role']])->save();
        $user->syncRoles([$data['role']]);

        return back()->with('status', __('admin.staff.roleChanged', [
            'name' => $user->name,
            'role' => __('admin.users.roles.'.$data['role']),
        ]));
    }

    /**
     * Set a colleague's password.
     *
     * No current-password check, because the point is that this is used when
     * the colleague has lost theirs. It is admin-only and the person doing it
     * could suspend the account outright, so it grants nothing they did not
     * already have.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->guardStaff($user);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $user->forceFill(['password' => $data['password']])->save();

        return back()->with('status', __('admin.staff.passwordReset', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardStaff($user);

        if ($user->is($request->user())) {
            return back()->withErrors(['access' => __('admin.staff.cannotDeleteSelf')]);
        }

        if ($this->wouldLeaveNoAdmin($user, null)) {
            return back()->withErrors(['access' => __('admin.staff.lastAdmin')]);
        }

        // Soft delete: their name stays readable on every listing they ever
        // approved, which is the whole point of recording who reviewed what.
        $user->delete();

        return back()->with('status', __('admin.staff.removed', ['name' => $user->name]));
    }

    /** This screen manages staff, and nobody else. */
    private function guardStaff(User $user): void
    {
        abort_unless($user->isStaff(), 404);
    }

    /**
     * True when demoting or removing this account would leave the board with
     * no administrator at all.
     */
    private function wouldLeaveNoAdmin(User $user, ?string $newRole): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if ($newRole === User::ROLE_ADMIN) {
            return false;
        }

        return $this->adminCount() <= 1;
    }

    private function adminCount(): int
    {
        return User::query()->where('role', User::ROLE_ADMIN)->count();
    }
}
