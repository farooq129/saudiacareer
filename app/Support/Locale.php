<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/**
 * The one place that knows how the two languages are wired together.
 *
 * Every public route is registered twice from the same file — once unprefixed
 * under the `ar.` name prefix, once under `/en` with the `en.` prefix. So
 * `ar.jobs.show` and `en.jobs.show` are the same page in the two languages, and
 * switching language is a matter of swapping the prefix while keeping the route
 * parameters. Views never name a prefix: they call `lroute('jobs.show', …)`.
 */
class Locale
{
    /** Every language the board is published in. */
    public const SUPPORTED = ['ar', 'en'];

    /** The language served from the bare root, and the fallback everywhere. */
    public const DEFAULT = 'ar';

    public static function current(): string
    {
        return in_array(App::getLocale(), self::SUPPORTED, true)
            ? App::getLocale()
            : self::DEFAULT;
    }

    public static function other(): string
    {
        return self::current() === 'ar' ? 'en' : 'ar';
    }

    public static function isRtl(?string $locale = null): bool
    {
        return ($locale ?: self::current()) === 'ar';
    }

    /** The value for the `dir` attribute on <html>. */
    public static function dir(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    /** The native name of a language, for the language switcher. */
    public static function name(string $locale): string
    {
        return $locale === 'en' ? 'English' : 'العربية';
    }

    /**
     * A URL for a route in a given language.
     *
     * `$name` is the bare name — 'jobs.show', not 'ar.jobs.show'.
     */
    public static function route(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return route(($locale ?: self::current()).'.'.$name, $parameters, $absolute);
    }

    /**
     * The URL of the page being viewed, in a given language.
     *
     * This is what makes the language switcher keep your place instead of
     * dropping you on the home page, and what the hreflang tags point at.
     *
     * Falls back to that language's front page when the current request is not
     * a named board route — an error page, say — because there is then no
     * counterpart URL to offer.
     */
    public static function urlIn(string $locale): string
    {
        $route = Route::current();

        if ($route === null || $route->getName() === null) {
            return self::route('home', [], $locale);
        }

        $bare = preg_replace('/^(ar|en)\./', '', $route->getName());

        if (! Route::has($locale.'.'.$bare)) {
            return self::route('home', [], $locale);
        }

        $url = self::route($bare, $route->parameters(), $locale);

        // Carry the query string over, so a visitor switching language halfway
        // through a filtered result set keeps their filters.
        $query = request()->getQueryString();

        return $query ? $url.'?'.$query : $url;
    }

    /** The page being viewed, in the other language. */
    public static function switchUrl(): string
    {
        return self::urlIn(self::other());
    }
}
