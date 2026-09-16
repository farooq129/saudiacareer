@extends('layouts.portal')

@php
    use App\Models\User;
    use App\Support\Phone;
@endphp

@section('title', __('admin.staff.title'))
@section('heading', __('admin.staff.title'))

@section('content')

    <p class="text-muted" style="max-width:76ch;margin:0;font-size:13.5px">
        {{ __('admin.staff.intro') }}
    </p>

    {{-- ── Who works here ───────────────────────────────────────────────── --}}
    <section class="adm-panel">
        @if ($staff->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-shield-star"></i>
                <p style="margin:0">{{ __('admin.staff.empty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('admin.staff.role') }}</th>
                            <th>{{ __('admin.staff.canDo') }}</th>
                            <th>{{ __('admin.users.lastLogin') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($staff as $member)
                            @php $isSelf = $member->is(auth()->user()); @endphp

                            <tr>
                                <td>
                                    <span class="adm-cell-title">
                                        {{ $member->name }}
                                        @if ($isSelf)
                                            <span class="text-muted" style="font-weight:400">({{ __('admin.staff.you') }})</span>
                                        @endif
                                    </span>
                                    <span class="adm-cell-sub" dir="ltr">
                                        {{ Phone::format($member->phone) }}@if ($member->email) · {{ $member->email }} @endif
                                    </span>
                                </td>

                                <td>
                                    <span class="pill {{ $member->isAdmin() ? 'pill-published' : 'pill-reviewing' }}">
                                        <i class="ph {{ $member->isAdmin() ? 'ph-shield-star' : 'ph-eye' }}"></i>
                                        {{ __('admin.users.roles.'.$member->role) }}
                                    </span>
                                </td>

                                <td class="text-muted" style="font-size:12.5px;max-width:28ch">
                                    {{ $member->isAdmin() ? __('admin.staff.adminCan') : __('admin.staff.moderatorCan') }}
                                </td>

                                <td class="num">
                                    {{ $member->last_login_at?->diffForHumans() ?? __('admin.users.never') }}
                                </td>

                                <td style="width:1%">
                                    {{-- Nothing to do to your own row: you cannot
                                         change your own role or remove yourself,
                                         and your password lives on My account. --}}
                                    {{-- <details>, not an Alpine toggle: the
                                         browser opens and closes this on its
                                         own, so the actions are reachable even
                                         if the JavaScript never loads. --}}
                                    @unless ($isSelf)
                                        <details class="row-actions">
                                            <summary class="btn btn-secondary">
                                                <i class="ph ph-gear"></i>{{ __('admin.staff.manage') }}
                                            </summary>

                                            <div style="margin-top:var(--space-3);min-width:270px">
                                            <form method="POST" action="{{ lroute('admin.staff.role', $member) }}">
                                                @csrf @method('PATCH')
                                                <div class="field">
                                                    <label for="role-{{ $member->id }}">{{ __('admin.staff.changeRole') }}</label>
                                                    <select id="role-{{ $member->id }}" class="input" name="role">
                                                        @foreach (User::STAFF_ROLES as $role)
                                                            <option value="{{ $role }}" @selected($member->role === $role)>
                                                                {{ __('admin.users.roles.'.$role) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-secondary btn-block">
                                                    {{ __('admin.staff.changeRole') }}
                                                </button>
                                            </form>

                                            {{-- No current-password check here: the
                                                 point is that the colleague has lost
                                                 theirs. --}}
                                            <form method="POST" action="{{ lroute('admin.staff.password', $member) }}"
                                                  style="margin-top:var(--space-4)">
                                                @csrf @method('PATCH')
                                                <div class="field">
                                                    <label for="pw-{{ $member->id }}">
                                                        {{ __('admin.staff.resetPasswordFor', ['name' => $member->name]) }}
                                                    </label>
                                                    <input id="pw-{{ $member->id }}" class="input" name="password"
                                                           type="password" minlength="10" autocomplete="new-password">
                                                </div>
                                                <div class="field">
                                                    <input class="input" name="password_confirmation" type="password"
                                                           minlength="10" autocomplete="new-password"
                                                           placeholder="{{ __('admin.staff.confirmPassword') }}">
                                                </div>
                                                <button type="submit" class="btn btn-secondary btn-block">
                                                    {{ __('admin.staff.resetPassword') }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ lroute('admin.staff.destroy', $member) }}"
                                                  style="margin-top:var(--space-4)"
                                                  onsubmit="return confirm(@js(__('admin.staff.removeConfirm')))">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-secondary btn-block"
                                                        style="color:var(--color-cta)">
                                                    <i class="ph ph-trash"></i>{{ __('admin.staff.remove') }}
                                                </button>
                                            </form>
                                            </div>
                                        </details>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- ── Add someone ──────────────────────────────────────────────────────
         Always on the page, never behind a toggle. Creating an administrator
         is one of the few things on this board that cannot be done any other
         way, and a form that only appears once JavaScript has booted is a form
         that is missing entirely when it has not. --}}
    <section class="adm-panel">
        <div class="adm-panel-head">
            <h5>{{ __('admin.staff.addTitle') }}</h5>
        </div>

        <form method="POST" action="{{ lroute('admin.staff.store') }}" style="padding:var(--space-6)">
            @csrf

            <div class="adm-facts">
                <div class="field">
                    <label for="s-name">{{ __('admin.staff.name') }}</label>
                    <input id="s-name" class="input" name="name" required value="{{ old('name') }}">
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="s-phone">{{ __('admin.staff.phone') }}</label>
                    <input id="s-phone" class="input" name="phone" dir="ltr" inputmode="tel" required
                           placeholder="05X XXX XXXX" value="{{ old('phone') }}">
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="s-email">{{ __('admin.staff.email') }}</label>
                    <input id="s-email" class="input" name="email" type="email" dir="ltr" value="{{ old('email') }}">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="s-role">{{ __('admin.staff.role') }}</label>
                    <select id="s-role" class="input" name="role" required>
                        @foreach (User::STAFF_ROLES as $role)
                            <option value="{{ $role }}" @selected(old('role', User::ROLE_MODERATOR) === $role)>
                                {{ __('admin.users.roles.'.$role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="s-password">{{ __('admin.staff.password') }}</label>
                    <input id="s-password" class="input" name="password" type="password" minlength="10"
                           autocomplete="new-password" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="s-password2">{{ __('admin.staff.confirmPassword') }}</label>
                    <input id="s-password2" class="input" name="password_confirmation" type="password"
                           minlength="10" autocomplete="new-password" required>
                </div>
            </div>

            <p class="text-muted" style="font-size:12px;margin:var(--space-4) 0 0">
                {{ __('admin.staff.passwordHint') }}
            </p>

            <button type="submit" class="btn-blue" style="margin-top:var(--space-4)">
                {{ __('admin.staff.create') }}
            </button>
        </form>
    </section>

@endsection
