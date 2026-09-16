@extends('layouts.portal')

@php use App\Models\JobReport; @endphp

@section('title', __('admin.reports.title'))
@section('heading', __('admin.reports.title'))

@section('content')

    <form method="GET" action="{{ lroute('admin.reports.index') }}" class="adm-panel" style="padding:var(--space-6)">
        <div class="adm-filters">
            <div class="field">
                <label for="status">{{ __('admin.status.all') }}</label>
                <select id="status" class="input" name="status">
                    <option value="open" @selected($filters['status'] === 'open')>{{ __('admin.reports.open') }}</option>
                    <option value="upheld" @selected($filters['status'] === 'upheld')>{{ __('admin.reports.statuses.upheld') }}</option>
                    <option value="dismissed" @selected($filters['status'] === 'dismissed')>{{ __('admin.reports.statuses.dismissed') }}</option>
                    <option value="all" @selected($filters['status'] === 'all')>{{ __('admin.reports.all') }}</option>
                </select>
            </div>

            <div class="field">
                <label for="reason">{{ __('report.reason') }}</label>
                <select id="reason" class="input" name="reason">
                    <option value="all">{{ __('admin.reports.allReasons') }}</option>
                    @foreach (JobReport::REASONS as $reason)
                        <option value="{{ $reason }}" @selected($filters['reason'] === $reason)>{{ __('report.'.$reason) }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-blue">{{ __('search.submit') }}</button>
            <a href="{{ lroute('admin.reports.index') }}" class="btn btn-secondary">{{ __('results.clear') }}</a>
        </div>
    </form>

    <section class="adm-panel">
        @if ($reports->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-shield-check"></i>
                <p style="margin:0">{{ __('admin.reports.noReports') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.nav.jobs') }}</th>
                            <th>{{ __('report.reason') }}</th>
                            <th>{{ __('admin.reports.reportedBy') }}</th>
                            <th>{{ __('admin.listing.submitted') }}</th>
                            <th>{{ __('admin.status.all') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reports as $report)
                            <tr x-data="{ acting: false }">
                                <td>
                                    @if ($report->job)
                                        <a href="{{ lroute('admin.jobs.show', $report->job) }}" class="adm-cell-title">
                                            {{ $report->job->title }}
                                        </a>
                                        <span class="adm-cell-sub">
                                            {{ $report->job->company?->name }} · {{ $report->job->city?->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    {{ __('report.'.$report->reason) }}
                                    @if ($report->note)
                                        <span class="adm-cell-sub">{{ $report->note }}</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $report->user?->name ?? __('admin.reports.anonymous') }}
                                    @if ($report->resolved_at)
                                        <span class="adm-cell-sub">
                                            {{ __('admin.reports.resolvedBy', [
                                                'name' => $report->resolver?->name ?? '—',
                                                'date' => $report->resolved_at->isoFormat('D MMM YYYY'),
                                            ]) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="num">{{ $report->created_at->diffForHumans() }}</td>

                                <td>
                                    @include('admin.partials.status-pill', [
                                        'status' => $report->status,
                                        'group' => 'admin.reports.statuses',
                                    ])
                                </td>

                                <td style="width:1%">
                                    @if (in_array($report->status, [JobReport::STATUS_OPEN, JobReport::STATUS_REVIEWING], true))
                                        <div class="adm-row-actions">
                                            <button type="button" class="btn btn-secondary" @click="acting = ! acting">
                                                <i class="ph ph-gavel"></i>
                                            </button>
                                        </div>

                                        {{-- Upholding takes the listing down in the
                                             same action — the step most likely to be
                                             skipped if it needed another screen. --}}
                                        <div x-show="acting" x-cloak style="margin-top:var(--space-3);min-width:280px">
                                            <form method="POST" action="{{ lroute('admin.reports.uphold', $report) }}">
                                                @csrf @method('PATCH')
                                                <div class="field">
                                                    <label for="uphold-{{ $report->id }}">{{ __('admin.reports.upholdPrompt') }}</label>
                                                    <textarea id="uphold-{{ $report->id }}" class="input" name="note"
                                                              rows="2" required minlength="5"></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-secondary btn-block" style="color:var(--color-cta)">
                                                    {{ __('admin.reports.uphold') }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ lroute('admin.reports.dismiss', $report) }}"
                                                  style="margin-top:var(--space-3)">
                                                @csrf @method('PATCH')
                                                <div class="field">
                                                    <label for="dismiss-{{ $report->id }}">{{ __('admin.reports.dismissPrompt') }}</label>
                                                    <input id="dismiss-{{ $report->id }}" class="input" name="note" type="text">
                                                </div>
                                                <button type="submit" class="btn btn-secondary btn-block">
                                                    {{ __('admin.reports.dismiss') }}
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($report->resolution_note)
                                        <span class="adm-cell-sub" style="max-width:220px;display:block">
                                            {{ $report->resolution_note }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($reports->hasPages())
                <div style="padding:var(--space-6)">{{ $reports->links() }}</div>
            @endif
        @endif
    </section>

@endsection
