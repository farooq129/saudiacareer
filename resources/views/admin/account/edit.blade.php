@extends('layouts.portal')

@php use App\Support\Locale; @endphp

@section('title', __('admin.account.title'))
@section('heading', __('admin.account.title'))

@section('content')

    <div class="adm-detail">

        <div>
            <form method="POST" action="{{ lroute('admin.account.update') }}" class="adm-block">
                @csrf @method('PUT')

                <h5>{{ __('admin.account.details') }}</h5>

                <div class="field">
                    <label for="name">{{ __('Name') }}</label>
                    <input id="name" class="input" name="name" required value="{{ old('name', $user->name) }}">
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="phone">{{ __('Mobile number') }}</label>
                    <input id="phone" class="input" name="phone" dir="ltr" inputmode="tel" required
                           value="{{ old('phone', $user->phone) }}">
                    <span class="text-muted" style="font-size:12px">{{ __('admin.account.phoneHint') }}</span>
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="email">{{ __('Email (optional)') }}</label>
                    <input id="email" class="input" name="email" type="email" dir="ltr"
                           value="{{ old('email', $user->email) }}">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="locale">{{ __('admin.account.language') }}</label>
                    <select id="locale" class="input" name="locale">
                        @foreach (Locale::SUPPORTED as $code)
                            <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>
                                {{ Locale::name($code) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-blue btn-wide" style="margin-top:var(--space-6)">
                    {{ __('admin.account.save') }}
                </button>
            </form>
        </div>

        <aside>
            <div class="adm-block">
                <h5>{{ __('admin.account.yourRole') }}</h5>

                <span class="pill {{ $user->isAdmin() ? 'pill-published' : 'pill-reviewing' }}">
                    <i class="ph {{ $user->isAdmin() ? 'ph-shield-star' : 'ph-eye' }}"></i>
                    {{ __('admin.users.roles.'.$user->role) }}
                </span>

                <p class="text-muted" style="font-size:12.5px;margin:var(--space-4) 0 0">
                    {{ $user->isAdmin() ? __('admin.staff.adminCan') : __('admin.staff.moderatorCan') }}
                </p>
            </div>

            {{-- Separate form and separate submit: a password change should not
                 ride along with a name edit, and the current password is asked
                 for even though they are signed in. --}}
            <form method="POST" action="{{ lroute('admin.account.password') }}" class="adm-block">
                @csrf @method('PUT')

                <h5>{{ __('admin.account.password') }}</h5>

                <div class="field">
                    <label for="current_password">{{ __('admin.account.currentPassword') }}</label>
                    <input id="current_password" class="input" name="current_password" type="password"
                           autocomplete="current-password" required>
                    @error('current_password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="password">{{ __('admin.account.newPassword') }}</label>
                    <input id="password" class="input" name="password" type="password" minlength="10"
                           autocomplete="new-password" required>
                    <span class="text-muted" style="font-size:12px">{{ __('admin.account.passwordHint') }}</span>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="password_confirmation">{{ __('admin.account.confirmPassword') }}</label>
                    <input id="password_confirmation" class="input" name="password_confirmation" type="password"
                           minlength="10" autocomplete="new-password" required>
                </div>

                <p class="text-muted" style="font-size:12px;margin:var(--space-4) 0 0">
                    {{ __('admin.account.signOutOthers') }}
                </p>

                <button type="submit" class="btn btn-secondary btn-block" style="margin-top:var(--space-4)">
                    {{ __('admin.account.password') }}
                </button>
            </form>
        </aside>
    </div>

@endsection
