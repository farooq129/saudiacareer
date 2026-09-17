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

    <title>@yield('title', $brand.__('brand.tld'))</title>
    <meta name="description" content="@yield('description', __('footer.tagline'))">

    {{--
        Both languages are real, crawlable pages rather than one page with a
        toggle, so each declares the other as its alternate. Without these a
        search engine reads them as duplicates and picks one to drop.
    --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="alternate" hreflang="ar" href="{{ Locale::urlIn('ar') }}">
    <link rel="alternate" hreflang="en" href="{{ Locale::urlIn('en') }}">
    <link rel="alternate" hreflang="x-default" href="{{ Locale::urlIn('ar') }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- IBM Plex Sans Arabic, self-hosted by the Vite font plugin. --}}
    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body>

{{--
    Everything lives inside .app, which carries dir and lang. The stylesheet
    themes off both — the accent ramp, the two font stacks and the hero scrim's
    direction are all selected from this one element, so the whole page
    re-themes when the language changes.
--}}
<div class="app" dir="{{ $dir }}" lang="{{ $locale }}"
     style="min-height:100vh;background:var(--color-bg);color:var(--color-text);font-family:var(--font-body);line-height:1.7"
     x-data="board">

    <a href="#main" class="sr-only">{{ __('Skip to content') }}</a>

    @include('partials.header')
    @include('partials.drawer')

    <main id="main">
        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->has('access'))
            <div class="flash flash-error">{{ $errors->first('access') }}</div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')

    @stack('dialogs')
</div>

@stack('scripts')
</body>
</html>
