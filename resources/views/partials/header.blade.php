@php
    use App\Support\Locale;

    $locale = Locale::current();
    $isAr = $locale === 'ar';
    $brand = config('board.brand.'.$locale);

    // Direction-aware glyphs — the arrow has to point the way reading goes.
    $arrowIcon = $isAr ? 'ph-arrow-left' : 'ph-arrow-right';

    // The hero carries the search on the home page, so the compact one in the
    // bar only appears where there is no hero.
    $showHeaderSearch = ! request()->routeIs('*.home');
@endphp

<header class="hdr">

    {{-- The thin utility strip: audience links and the language segment. --}}
    <div class="hdr-util">
        <div class="hdr-row">
            <div class="hdr-util-links">
                <a href="{{ lroute('jobs.index') }}">{{ __('util.seekers') }}</a>
                <span class="hdr-util-sep"></span>
                <a href="{{ lroute('seekers.index') }}">{{ __('util.employers') }}</a>
            </div>

            <div style="display:flex;align-items:center;gap:var(--space-3);margin-inline-start:auto">
                {{--
                    Two links, not a dropdown: with exactly two languages the
                    alternative should always be visible. Each points at this
                    same page in that language — the URL is what decides the
                    language, so the link is the whole mechanism and it stays
                    shareable and crawlable.
                --}}
                <div class="lang-seg" role="group" aria-label="{{ __('util.language') }}">
                    <a href="{{ Locale::urlIn('ar') }}" hreflang="ar" lang="ar"
                       class="lang-opt {{ $isAr ? 'is-on' : '' }}"
                       @if ($isAr) aria-current="true" @endif>العربية</a>
                    <a href="{{ Locale::urlIn('en') }}" hreflang="en" lang="en"
                       class="lang-opt {{ $isAr ? '' : 'is-on' }}"
                       @if (! $isAr) aria-current="true" @endif>English</a>
                </div>
            </div>
        </div>
    </div>

    <div class="hdr-main">
        <div class="hdr-row">

            <button type="button" class="icon-btn burger" aria-label="{{ __('nav.menu') }}"
                    @click="drawer = true">
                <i class="ph ph-list"></i>
            </button>

            <a href="{{ lroute('home') }}" class="brand">
                <span class="brand-mark"><i class="ph ph-briefcase"></i></span>
                <span>{{ $brand }}<span class="brand-tld">{{ __('brand.tld') }}</span></span>
            </a>

            <nav class="hdr-nav">

                {{-- Categories. Only one menu is ever open: opening either
                     closes the other, and the transparent scrim behind both
                     closes whichever is showing. --}}
                <span class="nav-drop">
                    <button type="button" class="hdr-link" :class="mega && 'is-open'"
                            :aria-expanded="mega.toString()" aria-controls="mega-menu"
                            @click="mega = !mega; ai = false">
                        {{ __('nav.browse') }}<i class="ph ph-caret-down caret"></i>
                    </button>

                    <div id="mega-menu" class="mega" x-show="mega" x-cloak>
                        <div class="mega-inner">
                            <div class="mega-h">{{ __('nav.allCategories') }}</div>

                            <div class="mega-grid">
                                @foreach ($navCategories as $category)
                                    <a href="{{ lroute('jobs.index', ['cat' => $category->key]) }}" class="mega-item">
                                        @if ($category->svg_path)
                                            <img src="{{ asset($category->svg_path) }}" alt="" width="20" height="20">
                                        @else
                                            <i class="ph {{ $category->icon }}"></i>
                                        @endif
                                        {{ $category->name }}
                                    </a>
                                @endforeach
                            </div>

                            <div class="mega-foot">
                                <a href="{{ lroute('jobs.index') }}" class="btn btn-ghost">
                                    {{ __('nav.viewAll') }}<i class="ph {{ $arrowIcon }}" style="margin-inline-start:5px"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </span>

                {{-- AI tools. Keys and artwork come from config/board.php; every
                     visible string is in lang/{locale}/ai.php under the same key. --}}
                <span class="nav-drop">
                    <button type="button" class="hdr-link" :class="ai && 'is-open'"
                            :aria-expanded="ai.toString()" aria-controls="ai-menu"
                            @click="ai = !ai; mega = false">
                        <i class="ph ph-sparkle ai-mark"></i>{{ __('ai.nav') }}<i class="ph ph-caret-down caret"></i>
                    </button>

                    <div id="ai-menu" class="ai-menu" x-show="ai" x-cloak>
                        @foreach (config('board.ai_tools') as $tool)
                            <a href="#" class="ai-item">
                                <span class="ai-ico">
                                    <img src="{{ asset($tool['svg']) }}" alt="" width="28" height="28">
                                </span>
                                <span style="min-width:0">
                                    <span class="ai-t">{{ __('ai.tools.'.$tool['key'].'.title') }}</span>
                                    <span class="ai-d">{{ __('ai.tools.'.$tool['key'].'.body') }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </span>

            </nav>

            <div class="hdr-actions">
                @if ($showHeaderSearch)
                    <form class="hdr-search" method="GET" action="{{ lroute('jobs.index') }}" role="search">
                        <i class="ph ph-magnifying-glass" style="font-size:15px"></i>
                        <label class="sr-only" for="hdr-q">{{ __('search.keyword') }}</label>
                        <input id="hdr-q" type="search" name="q"
                               placeholder="{{ __('nav.searchPh') }}"
                               value="{{ request('q') }}">
                    </form>
                @endif

                @auth
                    {{-- Signed in, the primary action is "take me to my area",
                         not "sign out" — whichever area that is depends on the
                         account's role, which RoleRouteResolver owns. --}}
                    <a href="{{ \App\Services\RoleRouteResolver::homeUrlFor(auth()->user()) }}"
                       class="btn-blue hdr-signin" style="min-height:36px">
                        <i class="ph ph-user-circle"></i>{{ __('nav.myAccount') }}
                    </a>

                    <form method="POST" action="{{ lroute('logout') }}" class="hdr-signin">
                        @csrf
                        <button type="submit" class="hdr-link">{{ __('Sign out') }}</button>
                    </form>
                @else
                    <a href="{{ lroute('login') }}" class="btn-blue hdr-signin" style="min-height:36px">
                        {{ __('nav.signIn') }}
                    </a>
                @endauth

                {{-- The one red action on the page. --}}
                <a href="{{ lroute('register', ['as' => 'employer']) }}" class="btn-cta" style="min-height:36px">
                    {{ __('nav.placeAd') }}
                </a>
            </div>

        </div>
    </div>

    <div style="height:1px;background:linear-gradient(to right,transparent,var(--color-divider) 48px,var(--color-divider) calc(100% - 48px),transparent)"></div>

    {{-- A transparent scrim behind whichever panel is open, so a click anywhere
         else on the page closes it. --}}
    <div class="mega-scrim" x-show="mega || ai" x-cloak @click="mega = false; ai = false"></div>

</header>
