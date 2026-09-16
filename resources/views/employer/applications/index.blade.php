@extends('layouts.portal')

@section('title', __('employer.applications.title'))
@section('heading', __('employer.applications.title'))

@section('content')

    <form method="GET" action="{{ lroute('employer.applications.index') }}" class="adm-panel" style="padding:var(--space-6)">
        <div class="adm-filters">
            <div class="field field-grow">
                <label for="job">{{ __('employer.applications.appliedFor') }}</label>
                <select id="job" class="input" name="job" onchange="this.form.submit()">
                    <option value="all">{{ __('employer.applications.allJobs') }}</option>
                    @foreach ($jobs as $job)
                        <option value="{{ $job->id }}" @selected((string) $filters['job'] === (string) $job->id)>
                            {{ $job->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="status">{{ __('admin.status.all') }}</label>
                <select id="status" class="input" name="status" onchange="this.form.submit()">
                    <option value="all">{{ __('employer.applications.allStatuses') }}</option>
                    @foreach (['sent', 'viewed', 'shortlisted', 'rejected', 'hired'] as $s)
                        <option value="{{ $s }}" @selected($filters['status'] === $s)>
                            {{ __('employer.applications.statuses.'.$s) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <a href="{{ lroute('employer.applications.index') }}" class="btn btn-secondary">{{ __('results.clear') }}</a>
        </div>
    </form>

    <section class="adm-panel">
        @if ($applications->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-paper-plane-tilt"></i>
                <p style="margin:0">{{ __('employer.applications.empty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('employer.applications.applicant') }}</th>
                            <th>{{ __('employer.applications.appliedFor') }}</th>
                            <th>{{ __('employer.applications.received') }}</th>
                            <th>{{ __('admin.status.all') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>
                                    <a href="{{ lroute('employer.applications.show', $application) }}" class="adm-cell-title">
                                        {{ $application->user->name }}
                                    </a>
                                    @unless ($application->viewed_at)
                                        <span class="adm-cell-sub" style="color:var(--color-cta)">
                                            {{ __('employer.applications.statuses.sent') }}
                                        </span>
                                    @endunless
                                </td>

                                <td>{{ $application->job->title }}</td>
                                <td class="num">{{ $application->created_at->diffForHumans() }}</td>

                                <td>
                                    <span class="pill pill-{{ match ($application->status) {
                                        'shortlisted', 'hired' => 'published',
                                        'rejected' => 'rejected',
                                        'viewed' => 'reviewing',
                                        default => 'pending',
                                    } }}">
                                        {{ __('employer.applications.statuses.'.$application->status) }}
                                    </span>
                                </td>

                                <td style="width:1%">
                                    <a href="{{ lroute('employer.applications.show', $application) }}" class="btn btn-secondary">
                                        <i class="ph ph-eye"></i>
                                    </a>
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
