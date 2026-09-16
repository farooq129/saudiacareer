@php
    use App\Support\Locale;

    $locale = Locale::current();
    $isAr = $locale === 'ar';
    $brand = config('board.brand.'.$locale);

    // Eight categories, not sixteen: the drawer is a shortcut, and the full
    // list is one tap further on at "view all jobs".
    $drawerCats = $navCategories->take(8);
@endphp

<div x-show="drawer" x-cloak>
    <div class="scrim" @click="drawer = false"></div>

    {{-- dir is repeated here because a fixed box resolves logical insets
         against its own writing mode, not the app root's. --}}
    <div class="drawer" dir="{{ Locale::dir() }}">

        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-6)">
            <a href="{{ lroute('home') }}" class="brand" style="font-size:17px">
                <span class="brand-mark" style="width:28px;height:28px;font-size:15px">
                    <i class="ph ph-briefcase"></i>
                </span>
                <span>{{ $brand }}<span class="brand-tld">{{ __('brand.tld') }}</span></span>
            </a>
            <button type="button" class="icon-btn" style="margin-inline-start:auto"
                    aria-label="{{ __('nav.close') }}" @click="drawer = false">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <div class="lang-seg" style="align-self:flex-start;margin-bottom:var(--space-6)">
            <a href="{{ Locale::urlIn('ar') }}" lang="ar" class="lang-opt {{ $isAr ? 'is-on' : '' }}">العربية</a>
            <a href="{{ Locale::urlIn('en') }}" lang="en" class="lang-opt {{ $isAr ? '' : 'is-on' }}">English</a>
        </div>

        <a href="{{ lroute('saved.index') }}" class="drawer-link">
            <i class="ph ph-bookmark-simple"></i>{{ __('nav.saved') }}
            @if ($savedCount > 0)
                <span class="tag tag-accent" style="margin-inline-start:auto">{{ $savedCount }}</span>
            @endif
        </a>

        <a href="{{ lroute('seekers.index') }}" class="drawer-link">
            <i class="ph ph-users-three"></i>{{ __('home.seekerPosts') }}
        </a>

        <div class="mega-h" style="margin-top:var(--space-8)">
            <i class="ph ph-sparkle ai-mark" style="font-size:13px"></i>{{ __('ai.nav') }}
        </div>
        @foreach (config('board.ai_tools') as $tool)
            <a href="#" class="drawer-link" style="padding:10px 4px;font-size:14px">
                <img src="{{ asset($tool['svg']) }}" alt="" width="22" height="22">
                {{ __('ai.tools.'.$tool['key'].'.title') }}
            </a>
        @endforeach

        <div class="mega-h" style="margin-top:var(--space-8)">{{ __('nav.allCategories') }}</div>
        @foreach ($drawerCats as $category)
            <a href="{{ lroute('jobs.index', ['cat' => $category->key]) }}"
               class="drawer-link" style="padding:10px 4px;font-size:14px">
                @if ($category->svg_path)
                    <img src="{{ asset($category->svg_path) }}" alt="" width="22" height="22">
                @else
                    <i class="ph {{ $category->icon }}"></i>
                @endif
                {{ $category->name }}
            </a>
        @endforeach

        <div style="display:flex;flex-direction:column;gap:var(--space-2);margin-top:var(--space-8)">
            @auth
                <a href="{{ \App\Services\RoleRouteResolver::homeUrlFor(auth()->user()) }}" class="btn-blue btn-wide">
                    {{ __('nav.myAccount') }}
                </a>
                <form method="POST" action="{{ lroute('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-block">{{ __('Sign out') }}</button>
                </form>
            @else
                <a href="{{ lroute('login') }}" class="btn-blue btn-wide">{{ __('nav.signIn') }}</a>
            @endauth
            <a href="{{ lroute('register', ['as' => 'employer']) }}" class="btn-cta btn-wide">
                {{ __('nav.placeAd') }}
            </a>
        </div>

    </div>
</div>
