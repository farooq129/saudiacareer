@extends('layouts.portal')

@section('title', __('seeker.applications.title'))
@section('heading', __('seeker.applications.title'))

@section('content')

    <section class="adm-panel">
        <div class="adm-tabs">
            @foreach (['all', 'sent', 'viewed', 'shortlisted', 'rejected', 'hired'] as $tab)
                <a href="{{ lroute('seeker.applications.index', ['status' => $tab]) }}"
                   class="adm-tab {{ $status === $tab ? 'is-on' : '' }}">
                    {{ $tab === 'all' ? __('seeker.applications.allStatuses') : __('seeker.applications.statuses.'.$tab) }}
                </a>
            @endforeach
        </div>

        @if ($applications->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-paper-plane-tilt"></i>
                <p style="margin:0">{{ __('seeker.applications.empty') }}</p>
                <a href="{{ lroute('jobs.index') }}" class="btn-blue">{{ __('seeker.dashboard.browseJobs') }}</a>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('seeker.applications.job') }}</th>
                            <th>{{ __('seeker.applications.employer') }}</th>
                            <th>{{ __('seeker.applications.sent') }}</th>
                            <th>{{ __('seeker.applications.status') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>
                                    <a href="{{ lroute('jobs.show', $application->job) }}" class="adm-cell-title">
                                        {{ $application->job->title }}
                                    </a>
                                    <span class="adm-cell-sub">
                                        {{ $application->job->city->name }} · {{ $application->job->category->name }}
                                    </span>
                                </td>

                                <td>{{ $application->job->company?->name ?? '—' }}</td>

                                <td class="num">
                                    {{ $application->created_at->isoFormat('D MMM YYYY') }}
                                    {{-- Whether an employer has opened it is the
                                         thing the applicant actually wants to
                                         know, so it sits next to the date. --}}
                                    <span class="adm-cell-sub">
                                        {{ $application->viewed_at
                                            ? __('seeker.applications.viewedOn', ['date' => $application->viewed_at->isoFormat('D MMM')])
                                            : __('seeker.applications.notViewed') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="pill pill-{{ match ($application->status) {
                                        'shortlisted', 'hired' => 'published',
                                        'rejected' => 'rejected',
                                        'viewed' => 'reviewing',
                                        default => 'pending',
                                    } }}">
                                        {{ __('seeker.applications.statuses.'.$application->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($applications->hasPages())
                <div style="padding:var(--space-6)">{{ $applications->links() }}</div>
            @endif
        @endif
    </section>

@endsection
