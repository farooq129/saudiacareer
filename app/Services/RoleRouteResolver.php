<?php

namespace App\Services;

use App\Models\User;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/**
 * Where each kind of account lands after signing in, and where a user who
 * wanders into someone else's area gets sent back to.
 *
 * One place, so the login flow, the role middleware and the 403 handler cannot
 * drift apart and bounce a user between two "your home is over there" answers.
 */
class RoleRouteResolver
{
    /** The bare route name of a user's own dashboard. */
    public static function homeRouteFor(?User $user): string
    {
        return match ($user?->role) {
            // A moderator lands in the same area; what differs is what the
            // sidebar offers once they are there.
            User::ROLE_ADMIN, User::ROLE_MODERATOR => 'admin.dashboard',
            User::ROLE_EMPLOYER => 'employer.dashboard',
            User::ROLE_SEEKER => 'seeker.dashboard',
            default => 'home',
        };
    }

    /**
     * The URL of that dashboard in the language the request is running in.
     *
     * Falls back to the board's front page while an area is still unbuilt, so
     * signing in never 500s during the build-out.
     */
    public static function homeUrlFor(?User $user): string
    {
        $name = self::homeRouteFor($user);

        if (! Route::has(Locale::current().'.'.$name)) {
            return Locale::route('home');
        }

        return Locale::route($name);
    }
}
