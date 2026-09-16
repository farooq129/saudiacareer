<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decides which language the request runs in.
 *
 * The URL is the authority, not a cookie: Arabic lives at `/` and English at
 * `/en/…`, and both are meant to be crawled and shared. A visitor who sends an
 * Arabic link to a colleague must have that colleague land in Arabic, whatever
 * the colleague's own last choice was — so a stored preference can never
 * override a prefix.
 *
 * The one URL that says nothing about language is the bare root, and that is
 * the only place the stored preference is allowed to decide.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // A returning English reader typing the bare domain should land in
        // English. Deep links are never redirected — only `/` itself, and only
        // on a GET, so a form post can never be bounced.
        if ($request->path() === '/' && $request->isMethod('GET')) {
            $preferred = $request->user()?->locale ?? $request->session()->get('locale');

            if ($preferred === 'en') {
                return redirect()->route('en.home');
            }
        }

        $locale = $request->segment(1) === 'en' ? 'en' : Locale::DEFAULT;

        App::setLocale($locale);

        // Remember the choice for that bare-root redirect next time.
        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
