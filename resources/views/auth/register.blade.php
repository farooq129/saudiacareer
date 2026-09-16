@extends('layouts.app')

@php use App\Models\User; @endphp

@section('title', __('Create an account').' — '.config('board.brand.'.app()->getLocale()))

@section('content')

    <div class="page-narrow" style="max-width:480px">

        <div class="rail">
            <h5>{{ __('Create an account') }}</h5>
        </div>

        <form method="POST" action="{{ lroute('register.store') }}"
              class="card elev-sm" style="padding:var(--space-8);gap:var(--space-6);border-radius:var(--radius-sm);background:var(--color-bg)">
            @csrf

            {{-- Which side of the board the account is for. Preselected from
                 ?as=employer, so someone arriving from "Place an Ad" does not
                 have to find this. --}}
            <fieldset style="border:0;padding:0;margin:0">
                <legend style="font-size:12px;margin-bottom:9px;padding:0">{{ __('I am') }}</legend>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:var(--space-3)">
                    @foreach ([User::ROLE_SEEKER => __('I am looking for work'), User::ROLE_EMPLOYER => __('I am hiring')] as $value => $label)
                        <label class="role-opt">
                            <input type="radio" name="role" value="{{ $value }}"
                                   @checked(old('role', $intendedRole) === $value)>
                            <span class="dot"></span>{{ $label }}
                        </label>
                    @endforeach
                </div>

                @error('role') <span class="field-error">{{ $message }}</span> @enderror
            </fieldset>

            <div class="field">
                <label for="name">{{ __('Name') }}</label>
                <input id="name" class="input" name="name" type="text" value="{{ old('name') }}"
                       required autocomplete="name">
                @error('name') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="phone">{{ __('Mobile number') }}</label>
                <input id="phone" class="input" name="phone" type="tel" dir="ltr" inputmode="tel"
                       value="{{ old('phone') }}" required autocomplete="tel" placeholder="05X XXX XXXX">
                @error('phone') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="email">{{ __('Email (optional)') }}</label>
                <input id="email" class="input" name="email" type="email" dir="ltr"
                       value="{{ old('email') }}" autocomplete="email">
                @error('email') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" class="input" name="password" type="password" required
                       autocomplete="new-password">
                @error('password') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">{{ __('Confirm password') }}</label>
                <input id="password_confirmation" class="input" name="password_confirmation"
                       type="password" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-blue btn-wide">{{ __('Create an account') }}</button>

            <p class="text-muted" style="margin:0;font-size:13px;text-align:center">
                {{ __('Already have an account?') }}
                <a href="{{ lroute('login') }}">{{ __('nav.signIn') }}</a>
            </p>
        </form>

    </div>

@endsection
