<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleRouteResolver;
use App\Services\SavedJobService;
use App\Support\Locale;
use App\Support\Phone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private readonly SavedJobService $saved) {}

    public function create(Request $request): View
    {
        // ?as=employer comes off the "Place an Ad" call to action, so an
        // employer who arrives that way does not have to find the toggle.
        return view('auth.register', [
            'intendedRole' => $request->query('as') === 'employer'
                ? User::ROLE_EMPLOYER
                : User::ROLE_SEEKER,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in([User::ROLE_SEEKER, User::ROLE_EMPLOYER])],
        ]);

        $phone = Phone::normalise($data['phone']);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => __('Please enter a valid Saudi mobile number.'),
            ]);
        }

        if (User::query()->where('phone', $phone)->withTrashed()->exists()) {
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
            // Whichever language they registered in is the one we write to them
            // in, until they change it.
            'locale' => Locale::current(),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $this->saved->mergeIntoAccount($request, $user);

        return redirect()->to(RoleRouteResolver::homeUrlFor($user));
    }
}
