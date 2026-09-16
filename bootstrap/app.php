<?php

use App\Http\Middleware\EnsureRole;
use App\Services\RoleRouteResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SetLocale is applied per route group in routes/web.php rather than
        // globally, because it reads the language off the URL prefix and only
        // the board's own routes carry one. Health checks and the queue's
        // internal requests have no language.

        /*
         * Where the framework's own auth redirects go.
         *
         * Both defaults assume unprefixed route names — `route('login')` and
         * `/dashboard` — and neither exists here, because every route is
         * registered twice under `ar.` and `en.`. Left alone, a signed-out
         * visitor opening any guarded URL gets a RouteNotFoundException and a
         * 500 instead of the sign-in page.
         *
         * The language is read off the URL rather than through lroute(), so
         * these do not depend on SetLocale having already run: an English
         * visitor bounced off /en/admin lands on the English sign-in page.
         */
        $middleware->redirectGuestsTo(
            fn (Request $request) => route(($request->segment(1) === 'en' ? 'en' : 'ar').'.login'),
        );

        $middleware->redirectUsersTo(
            fn (Request $request) => RoleRouteResolver::homeUrlFor($request->user()),
        );

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            // The admin area is open to both staff roles; the parts of it that
            // change who works here, or who is verified, are admin-only.
            'ensure.staff' => EnsureRole::class.':admin,moderator',
            'ensure.admin' => EnsureRole::class.':admin',
            'ensure.employer' => EnsureRole::class.':employer',
            'ensure.seeker' => EnsureRole::class.':seeker',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A user who reaches an area their role does not own is returned to
        // their own dashboard rather than shown a bare 403 — the same
        // destination the login flow would have sent them to.
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('You do not have access to this area.')], 403);
            }

            return redirect()
                ->to(RoleRouteResolver::homeUrlFor($request->user()))
                ->withErrors(['access' => __('You do not have access to this area.')]);
        });
    })->create();
