<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | The wordmark differs per language rather than being transliterated — the
    | Arabic board is "مهنة.كوم" and the English one "Mihna.com". The suffix
    | lives in lang/{locale}/brand.php.
    |
    */

    'brand' => [
        'ar' => 'مهنة',
        'en' => 'Mihna',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI tools
    |--------------------------------------------------------------------------
    |
    | Keys and artwork only; every visible string lives in lang/{locale}/ai.php
    | under the same key. Adding a tool is one row here plus one entry per
    | language file — the pattern the design canvas established, kept so a tool
    | can never ship half-translated.
    |
    */

    'ai_tools' => [
        ['key' => 'cv', 'icon' => 'ph-file-text', 'svg' => 'icons/ui/ai-cv.svg'],
        ['key' => 'match', 'icon' => 'ph-target', 'svg' => 'icons/ui/ai-match.svg'],
        ['key' => 'letter', 'icon' => 'ph-note-pencil', 'svg' => 'icons/ui/ai-letter.svg'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Footer
    |--------------------------------------------------------------------------
    |
    | Column and link keys; labels live in lang/{locale}/footer.php. `route` is
    | the bare board route a link points at, resolved through lroute(). A null
    | route is a page that is not built yet and renders as plain text rather
    | than a dead link.
    |
    */

    'footer' => [
        [
            'key' => 'seekers',
            'links' => [
                ['key' => 'browseJobs', 'route' => 'jobs.index'],
                ['key' => 'categories', 'route' => 'jobs.index'],
                ['key' => 'cities', 'route' => 'jobs.index'],
                ['key' => 'ai', 'route' => null],
                ['key' => 'saved', 'route' => 'saved.index'],
            ],
        ],
        [
            'key' => 'employers',
            'links' => [
                ['key' => 'placeAd', 'route' => null],
                ['key' => 'pricing', 'route' => null],
                ['key' => 'login', 'route' => 'login'],
                ['key' => 'solutions', 'route' => null],
            ],
        ],
        [
            'key' => 'company',
            'links' => [
                ['key' => 'about', 'route' => null],
                ['key' => 'contact', 'route' => null],
                ['key' => 'advice', 'route' => null],
                ['key' => 'help', 'route' => null],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Listing lifecycle
    |--------------------------------------------------------------------------
    |
    | How long a listing stays up before it expires, and whether a new one goes
    | live immediately or waits for an admin. Moderation is on by default: a
    | free board with open posting is a target for fee scams, and the badge on
    | every card is only worth anything if something stands behind it.
    |
    */

    'listing' => [
        'lifetime_days' => (int) env('LISTING_LIFETIME_DAYS', 30),
        'moderated' => (bool) env('LISTING_MODERATED', true),
    ],

];
