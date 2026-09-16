<?php

use App\Support\Locale;

if (! function_exists('lroute')) {
    /**
     * A URL for a board route in the language the request is running in.
     *
     * Views call this instead of route(), so no Blade has to know that the
     * routes are registered twice under `ar.` and `en.` prefixes.
     */
    function lroute(string $name, mixed $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return Locale::route($name, $parameters, $locale, $absolute);
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return Locale::isRtl();
    }
}

if (! function_exists('sar')) {
    /**
     * A salary figure, grouped the way the design canvas groups it.
     *
     * Western digits with comma grouping in both languages: Saudi job ads quote
     * salaries this way, and Arabic-Indic digits in a salary range read as a
     * typo to most of the audience.
     */
    function sar(int|float|null $amount): string
    {
        return $amount === null ? '' : number_format((float) $amount, 0, '.', ',');
    }
}
