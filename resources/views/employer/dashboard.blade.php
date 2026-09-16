@extends('layouts.portal')

@section('title', __('employer.nav.dashboard'))
@section('heading', __('employer.nav.dashboard'))

@section('topAction')
    <a href="{{ lroute('employer.jobs.create') }}" class="btn-cta">
        <i class="ph ph-plus"></i>{{ __('employer.jobs.new') }}
    </a>
@endsection

@section('content')

    {{-- An employer with no company profile has no name beside their listings
         and can never be verified, so that gets said once, at the top. --}}
    @unless ($company)
        <div class="flash" style="margin-top:0">
            {{ __('employer.dashboard.completeProfile') }}
            <a href="{{ lroute('employer.company.edit') }}">{{ __('employer.company.title') }}</a>
        </div>
    @endunless

    <section>
        <div class="rail"><h5>{{ __('employer.dashboard.attention') }}</h5></div>

        <div class="adm-stats">
            <a href="{{ lroute('employer.applications.index') }}"
               class="adm-stat {{ $newApplications > 0 ? 'is-waiting' : 'is-clear' }}">
                <span class="adm-stat-k"><i class="ph ph-paper-plane-tilt"></i>{{ __('employer.dashboard.newApplications') }}</span>
                <span class="adm-stat-v">{{ sar($newApplications) }}</span>
            </a>

            <a href="{{ lroute('employer.jobs.index', ['status' => 'pending']) }}" class="adm-stat">
                <span class="adm-stat-k"><i class="ph ph-hourglass"></i>{{ __('employer.dashboard.pending') }}</span>
                <span class="adm-stat-v">{{ sar($pending) }}</span>
            </a>

            <a href="{{ lroute('employer.jobs.index', ['status' => 'rejected']) }}"
               class="adm-stat {{ $rejected > 0 ? 'is-waiting' : 'is-clear' }}">
                <span class="adm-stat-k"><i class="ph ph-x-circle"></i>{{ __('employer.dashboard.rejected') }}</span>
                <span class="adm-stat-v">{{ sar($rejected) }}</span>
            </a>

            <a href="{{ lroute('employer.jobs.index', ['status' => 'draft']) }}" class="adm-stat">
                <span class="adm-stat-k"><i class="ph ph-pencil-simple"></i>{{ __('employer.dashboard.drafts') }}</span>
                <span class="adm-stat-v">{{ sar($drafts) }}</span>
            </a>
        </div>
    </section>

    <section>
        <div class="rail"><h5>{{ __('employer.dashboard.performance') }}</h5></div>

        <div class="adm-stats">
            @foreach ([
                ['employer.dashboard.live', $live, 'ph-megaphone'],
                ['employer.dashboard.views', $totalViews, 'ph-eye'],
                ['employer.dashboard.applications', $totalApplications, 'ph-users-three'],
            ] as [$label, $value, $icon])
                <div class="adm-stat">
                    <span class="adm-stat-k"><i class="ph {{ $icon }}"></i>{{ __($label) }}</span>
                    <span class="adm-stat-v">{{ sar($value) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="adm-panel">
        <div class="adm-panel-head">
            <h5>{{ __('employer.dashboard.recentJobs') }}</h5>
            <a href="{{ lroute('employer.jobs.index') }}" class="adm-panel-more">{{ __('nav.viewAll') }}</a>
        </div>

        @if ($recentJobs->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-briefcase"></i>
                <p style="margin:0">{{ __('employer.dashboard.noJobs') }}</p>
                <a href="{{ lroute('employer.jobs.create') }}" class="btn-blue">{{ __('employer.jobs.new') }}</a>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <tbody>
                        @foreach ($recentJobs as $job)
                            <tr>
                                <td>
                                    <a href="{{ lroute('employer.jobs.edit', $job) }}" class="adm-cell-title">{{ $job->title }}</a>
                                    <span class="adm-cell-sub">{{ $job->city->name }} · {{ $job->category->name }}</span>
                                </td>
                                <td class="num">{{ sar($job->views_count) }} {{ __('employer.jobs.views') }}</td>
                                <td class="num">{{ sar($job->applications_count) }} {{ __('employer.jobs.applicants') }}</td>
                                <td>@include('admin.partials.status-pill', ['status' => $job->status])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="adm-panel">
        <div class="adm-panel-head">
            <h5>{{ __('employer.dashboard.recentApplications') }}</h5>
            <a href="{{ lroute('employer.applications.index') }}" class="adm-panel-more">{{ __('nav.viewAll') }}</a>
        </div>

        @if ($recentApplications->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-paper-plane-tilt"></i>
                <p style="margin:0">{{ __('employer.dashboard.noApplications') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <tbody>
                        @foreach ($recentApplications as $application)
                            <tr>
                                <td>
                                    <a href="{{ lroute('employer.applications.show', $application) }}" class="adm-cell-title">
                                        {{ $application->user->name }}
                                    </a>
                                    <span class="adm-cell-sub">{{ $application->job->title }}</span>
                                </td>
                                <td class="num">{{ $application->created_at->diffForHumans() }}</td>
                                <td>
                                    <span class="pill pill-{{ $application->viewed_at ? 'published' : 'pending' }}">
                                        {{ __('employer.applications.statuses.'.$application->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

@endsection
