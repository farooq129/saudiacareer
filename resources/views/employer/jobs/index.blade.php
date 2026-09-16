@extends('layouts.portal')

@php use App\Models\Job; @endphp

@section('title', __('employer.jobs.title'))
@section('heading', __('employer.jobs.title'))

@section('topAction')
    <a href="{{ lroute('employer.jobs.create') }}" class="btn-cta">
        <i class="ph ph-plus"></i>{{ __('employer.jobs.new') }}
    </a>
@endsection

@section('content')

    <section class="adm-panel">
        <div class="adm-tabs">
            @foreach (['all', 'published', 'pending', 'rejected', 'draft', 'filled', 'expired'] as $tab)
                <a href="{{ lroute('employer.jobs.index', ['status' => $tab]) }}"
                   class="adm-tab {{ $status === $tab ? 'is-on' : '' }}">
                    {{ __('admin.status.'.$tab) }}
                    <span class="adm-tab-n">{{ sar($statusCounts[$tab] ?? 0) }}</span>
                </a>
            @endforeach
        </div>

        @if ($jobs->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-briefcase"></i>
                <p style="margin:0">{{ __('employer.jobs.empty') }}</p>
                <a href="{{ lroute('employer.jobs.create') }}" class="btn-blue">{{ __('employer.jobs.new') }}</a>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('search.keyword') }}</th>
                            <th>{{ __('employer.jobs.views') }}</th>
                            <th>{{ __('employer.jobs.applicants') }}</th>
                            <th>{{ __('admin.listing.published') }}</th>
                            <th>{{ __('admin.status.all') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($jobs as $job)
                            <tr>
                                <td>
                                    <a href="{{ lroute('employer.jobs.edit', $job) }}" class="adm-cell-title">
                                        {{ $job->title }}
                                    </a>
                                    <span class="adm-cell-sub">
                                        {{ $job->city->name }} · {{ $job->category->name }} · {{ $job->employmentType->name }}
                                    </span>

                                    {{-- A rejection is only useful if the reason
                                         travels with it. --}}
                                    @if ($job->status === Job::STATUS_REJECTED && $job->rejection_reason)
                                        <span class="adm-cell-sub" style="color:var(--color-cta)">
                                            {{ $job->rejection_reason }}
                                        </span>
                                    @endif
                                </td>

                                <td class="num">{{ sar($job->views_count) }}</td>

                                <td class="num">
                                    @if ($job->applications_count > 0)
                                        <a href="{{ lroute('employer.applications.index', ['job' => $job->id]) }}">
                                            {{ sar($job->applications_count) }}
                                        </a>
                                    @else
                                        0
                                    @endif
                                </td>

                                <td class="num">{{ $job->published_at?->isoFormat('D MMM YYYY') ?? '—' }}</td>
                                <td>@include('admin.partials.status-pill', ['status' => $job->status])</td>

                                <td style="width:1%">
                                    <div class="adm-row-actions">
                                        <a href="{{ lroute('employer.jobs.edit', $job) }}" class="btn btn-secondary">
                                            <i class="ph ph-pencil-simple"></i>
                                        </a>

                                        @if ($job->status === Job::STATUS_PUBLISHED)
                                            <form method="POST" action="{{ lroute('employer.jobs.close', $job) }}"
                                                  onsubmit="return confirm(@js(__('employer.jobs.closeConfirm')))">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-secondary">
                                                    <i class="ph ph-seal-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ lroute('employer.jobs.destroy', $job) }}"
                                              onsubmit="return confirm(@js(__('employer.jobs.deleteConfirm')))">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-secondary" style="color:var(--color-cta)">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($jobs->hasPages())
                <div style="padding:var(--space-6)">{{ $jobs->links() }}</div>
            @endif
        @endif
    </section>

@endsection
