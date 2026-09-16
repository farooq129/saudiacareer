@extends('layouts.portal')

@php use App\Support\Phone; @endphp

@section('title', $company->name)
@section('heading', $company->name)

@section('content')

    <div class="adm-detail">

        <div>
            <div class="adm-block">
                <h5>{{ __('admin.listing.overview') }}</h5>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('admin.companies.crNumber') }}</dt>
                        <dd dir="ltr">{{ $company->cr_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.companies.memberSince') }}</dt>
                        <dd class="num">{{ $company->member_since ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.companies.staffCount') }}</dt>
                        <dd class="num">{{ $company->staff_count ? sar($company->staff_count) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('search.city') }}</dt>
                        <dd>{{ $company->city?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.companies.totalListings') }}</dt>
                        <dd class="num">{{ sar($company->jobs_count) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.companies.liveListings') }}</dt>
                        <dd class="num">{{ sar($company->live_jobs_count) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.listing.content') }}</h5>

                @foreach (['name', 'blurb'] as $base)
                    <div class="adm-bilingual" style="margin-bottom:var(--space-6)">
                        @foreach (['ar' => 'admin.listing.arabic', 'en' => 'admin.listing.english'] as $lang => $langLabel)
                            <div>
                                <span class="adm-lang-label">{{ __($langLabel) }}</span>
                                @if ($company->missingTranslation($base, $lang))
                                    <p class="adm-missing">{{ __('admin.listing.missing') }}</p>
                                @else
                                    <p style="margin:0">{{ $company->rawTranslation($base, $lang) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.companies.totalListings') }}</h5>

                @if ($jobs->isEmpty())
                    <p class="text-muted" style="margin:0">{{ __('admin.queue.empty') }}</p>
                @else
                    <div class="adm-table-wrap">
                        <table class="adm-table">
                            <tbody>
                                @foreach ($jobs as $job)
                                    <tr>
                                        <td>
                                            <a href="{{ lroute('admin.jobs.show', $job) }}" class="adm-cell-title">
                                                {{ $job->title }}
                                            </a>
                                            <span class="adm-cell-sub">{{ $job->city->name }} · {{ $job->category->name }}</span>
                                        </td>
                                        <td class="num">{{ $job->created_at->isoFormat('D MMM YYYY') }}</td>
                                        <td>@include('admin.partials.status-pill', ['status' => $job->status])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <aside>
            <div class="adm-block">
                <h5>{{ __('admin.companies.verified') }}</h5>

                @if ($company->is_verified)
                    <p style="margin:0 0 var(--space-4);font-size:13px">
                        {{ __('admin.companies.verifiedBy', [
                            'name' => $company->verifier?->name ?? '—',
                            'date' => $company->verified_at?->isoFormat('D MMM YYYY') ?? '—',
                        ]) }}
                    </p>

                    <form method="POST" action="{{ lroute('admin.companies.unverify', $company) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-secondary btn-block">
                            {{ __('admin.companies.unverify') }}
                        </button>
                    </form>
                @else
                    <p class="text-muted" style="margin:0 0 var(--space-4);font-size:13px">
                        {{ __('admin.companies.verifyConfirm') }}
                    </p>

                    <form method="POST" action="{{ lroute('admin.companies.verify', $company) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-blue btn-wide">
                            <i class="ph ph-seal-check"></i>{{ __('admin.companies.verify') }}
                        </button>
                    </form>
                @endif
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.companies.owner') }}</h5>

                <p style="margin:0">
                    <span class="adm-cell-title">{{ $company->user->name }}</span>
                    <span class="adm-cell-sub" dir="ltr">{{ Phone::format($company->user->phone) }}</span>
                    @if ($company->user->email)
                        <span class="adm-cell-sub" dir="ltr">{{ $company->user->email }}</span>
                    @endif
                </p>
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.listing.contact') }}</h5>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('contactDialog.whatsapp') }}</dt>
                        <dd dir="ltr">{{ $company->whatsapp ? Phone::format($company->whatsapp) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('contactDialog.phone') }}</dt>
                        <dd dir="ltr">{{ $company->phone ? Phone::format($company->phone) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('contactDialog.email') }}</dt>
                        <dd dir="ltr" style="word-break:break-all">{{ $company->email ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>

@endsection
