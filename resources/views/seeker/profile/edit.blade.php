@extends('layouts.portal')

@php use App\Support\Locale; @endphp

@section('title', __('seeker.profile.title'))
@section('heading', __('seeker.profile.title'))

@section('content')

    <div class="adm-detail">

        <div>
            <form method="POST" action="{{ lroute('seeker.profile.update') }}" class="adm-block">
                @csrf @method('PUT')

                <h5>{{ __('seeker.profile.account') }}</h5>

                <div class="field">
                    <label for="name">{{ __('Name') }}</label>
                    <input id="name" class="input" name="name" required value="{{ old('name', $user->name) }}">
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="phone">{{ __('Mobile number') }}</label>
                    <input id="phone" class="input" name="phone" dir="ltr" inputmode="tel" required
                           value="{{ old('phone', $user->phone) }}">
                    <span class="text-muted" style="font-size:12px">{{ __('seeker.profile.phoneHint') }}</span>
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="email">{{ __('Email (optional)') }}</label>
                    <input id="email" class="input" name="email" type="email" dir="ltr"
                           value="{{ old('email', $user->email) }}">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="locale">{{ __('seeker.profile.language') }}</label>
                    <select id="locale" class="input" name="locale">
                        @foreach (Locale::SUPPORTED as $code)
                            <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>
                                {{ Locale::name($code) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-blue btn-wide" style="margin-top:var(--space-6)">
                    {{ __('seeker.profile.save') }}
                </button>
            </form>
        </div>

        <aside>
            <form method="POST" action="{{ lroute('seeker.profile.password') }}" class="adm-block">
                @csrf @method('PUT')

                <h5>{{ __('seeker.profile.password') }}</h5>

                <div class="field">
                    <label for="current_password">{{ __('seeker.profile.currentPassword') }}</label>
                    <input id="current_password" class="input" name="current_password" type="password"
                           autocomplete="current-password" required>
                    @error('current_password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="password">{{ __('seeker.profile.newPassword') }}</label>
                    <input id="password" class="input" name="password" type="password"
                           autocomplete="new-password" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="password_confirmation">{{ __('seeker.profile.confirmPassword') }}</label>
                    <input id="password_confirmation" class="input" name="password_confirmation" type="password"
                           autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-secondary btn-block" style="margin-top:var(--space-6)">
                    {{ __('seeker.profile.save') }}
                </button>
            </form>
        </aside>
    </div>

@endsection
