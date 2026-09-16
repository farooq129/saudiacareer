@php
    use App\Support\Locale;

    $locale = Locale::current();
    $dir = Locale::dir();
    $brand = config('board.brand.'.$locale);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- No signed-in area is indexed: it is behind auth, and a crawler
         following a leaked link should not put these URLs in a result page. --}}
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', $portalTitle) — {{ $brand }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body>

{{--
    One shell for all three signed-in areas — admin, employer and seeker. They
    differ only in the sidebar, which comes from $portalNav (see
    ViewServiceProvider), so a change to the chrome lands in every area at once
    rather than in whichever one someone remembered.
--}}
<div class="app admin" dir="{{ $dir }}" lang="{{ $locale }}" x-data="board">

    <a href="#main" class="sr-only">{{ __('Skip to content') }}</a>

    <aside class="adm-side" :class="drawer && 'is-open'">
        <div class="adm-side-head">
            <a href="{{ lroute('home') }}" class="brand" style="font-size:17px">
                <span class="brand-mark" style="width:28px;height:28px;font-size:15px">
                    <i class="ph ph-briefcase"></i>
                </span>
                <span>{{ $brand }}</span>
            </a>
            <button type="button" class="icon-btn adm-side-close" @click="drawer = false"
                    aria-label="{{ __('nav.close') }}">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <nav class="adm-nav" aria-label="{{ $portalTitle }}">
            @foreach ($portalNav as $item)
                <a href="{{ $item['url'] }}" class="adm-nav-link {{ $item['active'] ? 'is-on' : '' }}">
                    <i class="ph {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="adm-badge">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="adm-side-foot">
            <a href="{{ lroute('home') }}" class="adm-nav-link">
                <i class="ph ph-arrow-u-up-left"></i>
                <span>{{ __('admin.backToBoard') }}</span>
            </a>
        </div>
    </aside>

    <div class="scrim adm-scrim" x-show="drawer" x-cloak @click="drawer = false"></div>

    <div class="adm-main">

        <header class="adm-top">
            <button type="button" class="icon-btn adm-burger" @click="drawer = true"
                    aria-label="{{ __('nav.menu') }}">
                <i class="ph ph-list"></i>
            </button>

            <h1 class="adm-top-title">@yield('heading', $portalTitle)</h1>

            <div class="adm-top-actions">
                @hasSection('topAction')
                    @yield('topAction')
                @endif

                <div class="lang-seg">
                    <a href="{{ Locale::urlIn('ar') }}" lang="ar"
                       class="lang-opt {{ $locale === 'ar' ? 'is-on' : '' }}">العربية</a>
                    <a href="{{ Locale::urlIn('en') }}" lang="en"
                       class="lang-opt {{ $locale === 'en' ? 'is-on' : '' }}">English</a>
                </div>

                <span class="adm-who">
                    <i class="ph ph-user-circle"></i>{{ auth()->user()->name }}
                </span>

                <form method="POST" action="{{ lroute('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">{{ __('Sign out') }}</button>
                </form>
            </div>
        </header>

        <main id="main" class="adm-body">
            @if (session('status'))
                <div class="flash" style="margin-top:0">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="flash flash-error" style="margin-top:0">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>

    </div>
</div>

@stack('scripts')
</body>
</html>
