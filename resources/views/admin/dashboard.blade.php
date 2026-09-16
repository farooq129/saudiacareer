@extends('layouts.portal')

@section('title', __('admin.nav.dashboard'))
@section('heading', __('admin.nav.dashboard'))

@section('content')

    {{-- ── What is waiting ──────────────────────────────────────────────────
         First, above everything else. A moderation dashboard that opens on last
         month's signups buries the queue that is actually the job. --}}
    <section>
        <div class="rail">
            <h5>{{ __('admin.dashboard.waiting') }}</h5>
        </div>

        <div class="adm-stats">
            @foreach ([
                ['admin.jobs.index', 'admin.dashboard.pendingJobs', $pendingJobs, 'ph-briefcase', ['status' => 'pending']],
                ['admin.seekers.index', 'admin.dashboard.pendingPosts', $pendingSeekerPosts, 'ph-users-three', ['status' => 'pending']],
                ['admin.reports.index', 'admin.dashboard.openReports', $openReports, 'ph-flag', ['status' => 'open']],
                ['admin.companies.index', 'admin.dashboard.unverifiedCompanies', $unverifiedCompanies, 'ph-buildings', ['verified' => 'no']],
            ] as [$route, $label, $value, $icon, $query])
                <a href="{{ lroute($route, $query) }}"
                   class="adm-stat {{ $value > 0 ? 'is-waiting' : 'is-clear' }}">
                    <span class="adm-stat-k"><i class="ph {{ $icon }}"></i>{{ __($label) }}</span>
                    <span class="adm-stat-v">{{ sar($value) }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── The board at a glance ────────────────────────────────────────── --}}
    <section>
        <div class="rail">
            <h5>{{ __('admin.dashboard.board') }}</h5>
        </div>

        <div class="adm-stats">
            @foreach ([
                ['admin.dashboard.liveJobs', $liveJobs, 'ph-megaphone'],
                ['admin.dashboard.livePosts', $livePosts, 'ph-user-list'],
                ['admin.dashboard.employers', $employers, 'ph-buildings'],
                ['admin.dashboard.seekers', $seekers, 'ph-users-three'],
                ['admin.dashboard.applications', $applications, 'ph-paper-plane-tilt'],
            ] as [$label, $value, $icon])
                <div class="adm-stat">
                    <span class="adm-stat-k"><i class="ph {{ $icon }}"></i>{{ __($label) }}</span>
                    <span class="adm-stat-v">{{ sar($value) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── The queues themselves, actionable from here ──────────────────── --}}
    <section class="adm-panel">
        <div class="adm-panel-head">
            <h5>{{ __('admin.dashboard.pendingJobs') }}</h5>
            <a href="{{ lroute('admin.jobs.index', ['status' => 'pending']) }}" class="adm-panel-more">
                {{ __('admin.dashboard.reviewAll') }}
            </a>
        </div>

        @if ($latestPendingJobs->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-check-circle"></i>
                <p style="margin:0">{{ __('admin.dashboard.queueEmpty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <tbody>
                        @foreach ($latestPendingJobs as $job)
                            <tr>
                                <td>
                                    <a href="{{ lroute('admin.jobs.show', $job) }}" class="adm-cell-title">
                                        {{ $job->title }}
                                    </a>
                                    <span class="adm-cell-sub">
                                        {{ $job->company?->name ?? $job->user->name }} ·
                                        {{ $job->city->name }} · {{ $job->category->name }}
                                    </span>
                                </td>
                                <td class="num">{{ $job->created_at->diffForHumans() }}</td>
                                <td style="width:1%">
                                    <form method="POST" action="{{ lroute('admin.jobs.approve', $job) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ph ph-check"></i>{{ __('admin.queue.approve') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="adm-panel">
        <div class="adm-panel-head">
            <h5>{{ __('admin.dashboard.pendingPosts') }}</h5>
            <a href="{{ lroute('admin.seekers.index', ['status' => 'pending']) }}" class="adm-panel-more">
                {{ __('admin.dashboard.reviewAll') }}
            </a>
        </div>

        @if ($latestPendingPosts->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-check-circle"></i>
                <p style="margin:0">{{ __('admin.dashboard.queueEmpty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <tbody>
                        @foreach ($latestPendingPosts as $post)
                            <tr>
                                <td>
                                    <a href="{{ lroute('admin.seekers.show', $post) }}" class="adm-cell-title">
                                        {{ $post->headline }}
                                    </a>
                                    <span class="adm-cell-sub">
                                        {{ $post->display_name }} · {{ $post->city->name }} · {{ $post->category->name }}
                                    </span>
                                </td>
                                <td class="num">{{ $post->created_at->diffForHumans() }}</td>
                                <td style="width:1%">
                                    <form method="POST" action="{{ lroute('admin.seekers.approve', $post) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ph ph-check"></i>{{ __('admin.queue.approve') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="adm-panel">
        <div class="adm-panel-head">
            <h5>{{ __('admin.reports.title') }}</h5>
            <a href="{{ lroute('admin.reports.index') }}" class="adm-panel-more">
                {{ __('admin.dashboard.reviewAll') }}
            </a>
        </div>

        @if ($latestReports->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-shield-check"></i>
                <p style="margin:0">{{ __('admin.reports.noReports') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <tbody>
                        @foreach ($latestReports as $report)
                            <tr>
                                <td>
                                    @if ($report->job)
                                        <a href="{{ lroute('admin.jobs.show', $report->job) }}" class="adm-cell-title">
                                            {{ $report->job->title }}
                                        </a>
                                    @endif
                                    <span class="adm-cell-sub">
                                        {{ __('report.'.$report->reason) }} ·
                                        {{ $report->user?->name ?? __('admin.reports.anonymous') }}
                                    </span>
                                </td>
                                <td class="num">{{ $report->created_at->diffForHumans() }}</td>
                                <td style="width:1%">
                                    @include('admin.partials.status-pill', [
                                        'status' => $report->status,
                                        'group' => 'admin.reports.statuses',
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

@endsection
