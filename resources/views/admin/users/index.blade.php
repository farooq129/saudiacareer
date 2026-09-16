@extends('layouts.portal')

@php
    use App\Models\User;
    use App\Support\Phone;
@endphp

@section('title', __('admin.users.title'))
@section('heading', __('admin.users.title'))

@section('content')

    <form method="GET" action="{{ lroute('admin.users.index') }}" class="adm-panel" style="padding:var(--space-6)">
        <div class="adm-filters">
            <div class="field field-grow">
                <label for="q">{{ __('admin.users.search') }}</label>
                <input id="q" class="input" type="search" name="q" value="{{ $filters['q'] }}">
            </div>

            <div class="field">
                <label for="role">{{ __('admin.users.role') }}</label>
                <select id="role" class="input" name="role">
                    <option value="all">{{ __('admin.users.allRoles') }}</option>
                    @foreach ([User::ROLE_SEEKER, User::ROLE_EMPLOYER, User::ROLE_ADMIN] as $role)
                        <option value="{{ $role }}" @selected($filters['role'] === $role)>
                            {{ __('admin.users.roles.'.$role) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="active">{{ __('admin.users.active') }}</label>
                <select id="active" class="input" name="active">
                    <option value="all" @selected($filters['active'] === 'all')>{{ __('admin.users.allStates') }}</option>
                    <option value="yes" @selected($filters['active'] === 'yes')>{{ __('admin.users.active') }}</option>
                    <option value="no" @selected($filters['active'] === 'no')>{{ __('admin.users.suspended') }}</option>
                </select>
            </div>

            <button type="submit" class="btn-blue">{{ __('search.submit') }}</button>
            <a href="{{ lroute('admin.users.index') }}" class="btn btn-secondary">{{ __('results.clear') }}</a>
        </div>
    </form>

    <section class="adm-panel">
        @if ($users->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-user-circle"></i>
                <p style="margin:0">{{ __('admin.queue.empty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('admin.users.role') }}</th>
                            <th>{{ __('admin.users.listings') }}</th>
                            <th>{{ __('admin.users.posts') }}</th>
                            <th>{{ __('admin.users.applications') }}</th>
                            <th>{{ __('admin.users.lastLogin') }}</th>
                            <th>{{ __('admin.users.active') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <span class="adm-cell-title">{{ $user->name }}</span>
                                    <span class="adm-cell-sub" dir="ltr">
                                        {{ Phone::format($user->phone) }}@if ($user->email) · {{ $user->email }} @endif
                                    </span>
                                </td>

                                <td>
                                    {{ __('admin.users.roles.'.$user->role) }}
                                    @if ($user->company)
                                        <span class="adm-cell-sub">
                                            <a href="{{ lroute('admin.companies.show', $user->company) }}">
                                                {{ $user->company->name }}
                                            </a>
                                        </span>
                                    @endif
                                </td>

                                <td class="num">{{ sar($user->jobs_count) }}</td>
                                <td class="num">{{ sar($user->seeker_posts_count) }}</td>
                                <td class="num">{{ sar($user->applications_count) }}</td>

                                <td class="num">
                                    {{ $user->last_login_at?->diffForHumans() ?? __('admin.users.never') }}
                                </td>

                                <td>
                                    @if ($user->is_active)
                                        <span class="pill pill-published">
                                            <i class="ph ph-check-circle"></i>{{ __('admin.users.active') }}
                                        </span>
                                    @else
                                        <span class="pill pill-rejected">
                                            <i class="ph ph-prohibit"></i>{{ __('admin.users.suspended') }}
                                        </span>
                                    @endif
                                </td>

                                <td style="width:1%">
                                    {{-- An admin cannot suspend themselves or a
                                         colleague: removing another admin's access
                                         belongs in a conversation, not a row action. --}}
                                    @unless ($user->isAdmin())
                                        <form method="POST" action="{{ lroute('admin.users.active', $user) }}"
                                              @if ($user->is_active) onsubmit="return confirm(@js(__('admin.users.suspendConfirm')))" @endif>
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-secondary"
                                                    @style(['color:var(--color-cta)' => $user->is_active])>
                                                {{ $user->is_active ? __('admin.users.suspend') : __('admin.users.restore') }}
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div style="padding:var(--space-6)">{{ $users->links() }}</div>
            @endif
        @endif
    </section>

@endsection
