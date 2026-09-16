<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RoleRouteResolver;
use App\Services\SavedJobService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly SavedJobService $saved) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $phone = Phone::normalise($data['phone']);

        // A malformed number and a wrong password give the same message, so the
        // form cannot be used to find out which numbers have accounts.
        if ($phone === null || ! Auth::attempt(
            ['phone' => $phone, 'password' => $data['password'], 'is_active' => true],
            (bool) ($data['remember'] ?? false),
        )) {
            throw ValidationException::withMessages([
                'phone' => __('Invalid login credentials.'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        // Whatever the visitor saved before signing in comes with them.
        $this->saved->mergeIntoAccount($request, $user);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(RoleRouteResolver::homeUrlFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(lroute('home'));
    }
}
