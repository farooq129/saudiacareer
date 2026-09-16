<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Locale;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The signed-in staff member's own account.
 *
 * Open to moderators as well as admins: everyone who works here needs to be
 * able to change their own password without asking someone else to do it for
 * them, and an account whose password can only be reset by a colleague is an
 * account whose password never gets changed.
 *
 * Nobody can change their own role here. Promoting yourself is not a thing.
 */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.account.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['nullable', Rule::in(Locale::SUPPORTED)],
        ]);

        $phone = Phone::normalise($data['phone']);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => __('Please enter a valid Saudi mobile number.'),
            ]);
        }

        // Soft-deleted accounts still hold their number, so they count here —
        // otherwise restoring one later collides on the unique index.
        $taken = $user->newQuery()
            ->withTrashed()
            ->where('phone', $phone)
            ->whereKeyNot($user->id)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'phone' => __('An account already exists for this number.'),
            ]);
        }

        $user->fill([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'locale' => $data['locale'] ?? $user->locale,
        ])->save();

        return back()->with('status', __('admin.account.saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $user = $request->user();

        // The current password is required even though they are signed in: an
        // unlocked laptop left on a desk should not be enough to take over the
        // account that approves everything on the board.
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('admin.account.wrongPassword'),
            ]);
        }

        $user->forceFill(['password' => $data['password']])->save();

        // A password change invalidates every other session but this one, so a
        // change made because a password may be compromised actually ends the
        // sessions it was changed to stop.
        Auth::logoutOtherDevices($data['password']);
        $request->session()->regenerate();

        return back()->with('status', __('admin.account.passwordChanged'));
    }
}
