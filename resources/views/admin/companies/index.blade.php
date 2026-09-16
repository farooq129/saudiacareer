@extends('layouts.portal')

@php use App\Support\Phone; @endphp

@section('title', __('admin.companies.title'))
@section('heading', __('admin.companies.title'))

@section('content')

    <form method="GET" action="{{ lroute('admin.companies.index') }}" class="adm-panel" style="padding:var(--space-6)">
        <div class="adm-filters">
            <div class="field field-grow">
                <label for="q">{{ __('admin.companies.search') }}</label>
                <input id="q" class="input" type="search" name="q" value="{{ $filters['q'] }}">
            </div>

            <div class="field">
                <label for="verified">{{ __('admin.companies.verified') }}</label>
                <select id="verified" class="input" name="verified">
                    <option value="all" @selected($filters['verified'] === 'all')>{{ __('admin.companies.allEmployers') }}</option>
                    <option value="no" @selected($filters['verified'] === 'no')>{{ __('admin.companies.unverified') }}</option>
                    <option value="yes" @selected($filters['verified'] === 'yes')>{{ __('admin.companies.verified') }}</option>
                </select>
            </div>

            <button type="submit" class="btn-blue">{{ __('search.submit') }}</button>
            <a href="{{ lroute('admin.companies.index') }}" class="btn btn-secondary">{{ __('results.clear') }}</a>
        </div>
    </form>

    <section class="adm-panel">
        @if ($companies->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-buildings"></i>
                <p style="margin:0">{{ __('admin.queue.empty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.listing.employer') }}</th>
                            <th>{{ __('admin.companies.crNumber') }}</th>
                            <th>{{ __('search.city') }}</th>
                            <th>{{ __('admin.companies.totalListings') }}</th>
                            <th>{{ __('admin.companies.liveListings') }}</th>
                            <th>{{ __('admin.companies.verified') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>
                                    <a href="{{ lroute('admin.companies.show', $company) }}" class="adm-cell-title">
                                        {{ $company->name }}
                                    </a>
                                    <span class="adm-cell-sub" dir="ltr">
                                        {{ $company->phone ? Phone::format($company->phone) : ($company->email ?? '—') }}
                                    </span>
                                </td>

                                <td dir="ltr">{{ $company->cr_number ?? '—' }}</td>
                                <td>{{ $company->city?->name ?? '—' }}</td>
                                <td class="num">{{ sar($company->jobs_count) }}</td>
                                <td class="num">{{ sar($company->live_jobs_count) }}</td>

                                <td>
                                    @if ($company->is_verified)
                                        <span class="pill pill-published">
                                            <i class="ph ph-seal-check"></i>{{ __('admin.companies.verified') }}
                                        </span>
                                    @else
                                        <span class="pill pill-pending">
                                            <i class="ph ph-hourglass"></i>{{ __('admin.companies.unverified') }}
                                        </span>
                                    @endif
                                </td>

                                <td style="width:1%">
                                    <div class="adm-row-actions">
                                        @if ($company->is_verified)
                                            <form method="POST" action="{{ lroute('admin.companies.unverify', $company) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-secondary">
                                                    {{ __('admin.companies.unverify') }}
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ lroute('admin.companies.verify', $company) }}"
                                                  onsubmit="return confirm(@js(__('admin.companies.verifyConfirm')))">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ph ph-seal-check"></i>{{ __('admin.companies.verify') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($companies->hasPages())
                <div style="padding:var(--space-6)">{{ $companies->links() }}</div>
            @endif
        @endif
    </section>

@endsection
