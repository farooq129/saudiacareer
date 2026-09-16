<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Support\Locale;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The seeker's own account.
 *
 * Changing the phone number changes the login handle, so it is checked for
 * uniqueness against soft-deleted accounts too and re-verification is dropped —
 * the new number has not been proved to belong to them.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('seeker.profile.edit', ['user' => $request->user()]);
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

        $changingPhone = $phone !== $user->phone;

        $user->fill([
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'locale' => $data['locale'] ?? $user->locale,
        ]);

        if ($changingPhone) {
            $user->phone_verified_at = null;
        }

        $user->save();

        return back()->with('status', __('seeker.flash.profileSaved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // The current password is required even though they are signed in: a
        // borrowed unlocked phone should not be enough to take over an account.
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('Invalid login credentials.'),
            ]);
        }

        $request->user()->forceFill(['password' => $data['password']])->save();

        return back()->with('status', __('seeker.flash.passwordChanged'));
    }
}
