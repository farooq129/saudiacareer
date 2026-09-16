<?php

namespace App\Http\Middleware;

use App\Services\RoleRouteResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate an area to one or more account roles: `ensure.employer`, or
 * `role:admin|employer` style with several passed in.
 *
 * This checks the coarse `users.role` column, not Spatie permissions. The two
 * layers answer different questions: the role says which area of the product
 * you belong to, a permission says what you may do once you are inside it.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->to(lroute('login'));
        }

        if (! in_array($user->role, $roles, true)) {
            return redirect()
                ->to(RoleRouteResolver::homeUrlFor($user))
                ->withErrors(['access' => __('You do not have access to this area.')]);
        }

        if (! $user->is_active) {
            return redirect()->to(lroute('home'))
                ->withErrors(['access' => __('This account is inactive. Please contact support.')]);
        }

        return $next($request);
    }
}
