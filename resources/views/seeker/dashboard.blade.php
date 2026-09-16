@extends('layouts.portal')

@section('title', __('seeker.nav.dashboard'))
@section('heading', __('seeker.nav.dashboard'))

@section('topAction')
    <a href="{{ lroute('jobs.index') }}" class="btn-blue">
        <i class="ph ph-magnifying-glass"></i>{{ __('seeker.dashboard.browseJobs') }}
    </a>
@endsection

@section('content')

    {{-- Shortlisted and viewed lead, not "applications sent" — a number that
         only ever goes up and tells the person nothing about where they stand. --}}
    <section>
        <div class="rail"><h5>{{ __('seeker.dashboard.progress') }}</h5></div>

        <div class="adm-stats">
            @foreach ([
                ['seeker.dashboard.shortlisted', $shortlisted, 'ph-star'],
                ['seeker.dashboard.viewed', $viewed, 'ph-eye'],
                ['seeker.dashboard.sent', $sent, 'ph-paper-plane-tilt'],
                ['seeker.dashboard.saved', $savedCount, 'ph-bookmark-simple'],
                ['seeker.dashboard.livePosts', $livePosts, 'ph-user-list'],
                ['seeker.dashboard.pendingPosts', $pendingPosts, 'ph-hourglass'],
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
            <h5>{{ __('seeker.dashboard.recentApplications') }}</h5>
            <a href="{{ lroute('seeker.applications.index') }}" class="adm-panel-more">{{ __('nav.viewAll') }}</a>
        </div>

        @if ($recentApplications->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-paper-plane-tilt"></i>
                <p style="margin:0">{{ __('seeker.dashboard.noApplications') }}</p>
                <a href="{{ lroute('jobs.index') }}" class="btn-blue">{{ __('seeker.dashboard.browseJobs') }}</a>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <tbody>
                        @foreach ($recentApplications as $application)
                            <tr>
                                <td>
                                    <a href="{{ lroute('jobs.show', $application->job) }}" class="adm-cell-title">
                                        {{ $application->job->title }}
                                    </a>
                                    <span class="adm-cell-sub">
                                        {{ $application->job->company?->name }} · {{ $application->job->city->name }}
                                    </span>
                                </td>
                                <td class="num">{{ $application->created_at->diffForHumans() }}</td>
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
        @endif
    </section>

    <section class="adm-panel">
        <div class="adm-panel-head">
            <h5>{{ __('seeker.dashboard.yourPosts') }}</h5>
            <a href="{{ lroute('seeker.posts.index') }}" class="adm-panel-more">{{ __('nav.viewAll') }}</a>
        </div>

        @if ($posts->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-user-list"></i>
                <p style="margin:0;max-width:46ch">{{ __('seeker.dashboard.noPosts') }}</p>
                <a href="{{ lroute('seeker.posts.create') }}" class="btn-cta">{{ __('seeker.dashboard.createPost') }}</a>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>
                                    <a href="{{ lroute('seeker.posts.edit', $post) }}" class="adm-cell-title">
                                        {{ $post->headline }}
                                    </a>
                                    <span class="adm-cell-sub">{{ $post->city->name }} · {{ $post->category->name }}</span>
                                </td>
                                <td class="num">{{ sar($post->views_count) }} {{ __('seeker.posts.views') }}</td>
                                <td>@include('admin.partials.status-pill', ['status' => $post->status])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

@endsection
