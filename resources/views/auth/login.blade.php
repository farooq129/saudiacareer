@extends('layouts.app')

@section('title', __('nav.signIn').' — '.config('board.brand.'.app()->getLocale()))

@section('content')

    <div class="page-narrow" style="max-width:440px">

        <div class="rail">
            <h5>{{ __('nav.signIn') }}</h5>
        </div>

        <form method="POST" action="{{ lroute('login.attempt') }}"
              class="card elev-sm" style="padding:var(--space-8);gap:var(--space-6);border-radius:var(--radius-sm);background:var(--color-bg)">
            @csrf

            <div class="field">
                <label for="phone">{{ __('Mobile number') }}</label>
                {{--
                    dir="ltr" on the input itself: a phone number reads
                    left-to-right in both languages, and letting it inherit RTL
                    puts the +966 on the wrong end while the user types.
                --}}
                <input id="phone" class="input" name="phone" type="tel" dir="ltr" inputmode="tel"
                       value="{{ old('phone') }}" required autofocus autocomplete="tel"
                       placeholder="05X XXX XXXX">
                @error('phone') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" class="input" name="password" type="password" required
                       autocomplete="current-password">
                @error('password') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <label class="radio" style="gap:8px">
                <input type="checkbox" name="remember" value="1" style="position:static;width:auto;height:auto;opacity:1">
                {{ __('Remember me') }}
            </label>

            <button type="submit" class="btn-blue btn-wide">{{ __('nav.signIn') }}</button>

            <p class="text-muted" style="margin:0;font-size:13px;text-align:center">
                {{ __("Don't have an account?") }}
                <a href="{{ lroute('register') }}">{{ __('Create an account') }}</a>
            </p>
        </form>

    </div>

@endsection
