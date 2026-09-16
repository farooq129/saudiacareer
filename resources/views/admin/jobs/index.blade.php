@extends('layouts.portal')

@php use App\Models\Job; @endphp

@section('title', __('admin.nav.jobs'))
@section('heading', __('admin.nav.jobs'))

@section('content')

    {{-- ── Filters ──────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ lroute('admin.jobs.index') }}" class="adm-panel" style="padding:var(--space-6)">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">

        <div class="adm-filters">
            <div class="field field-grow">
                <label for="q">{{ __('admin.queue.search') }}</label>
                <input id="q" class="input" type="search" name="q" value="{{ $filters['q'] }}">
            </div>

            <div class="field">
                <label for="city">{{ __('search.city') }}</label>
                <select id="city" class="input" name="city">
                    <option value="all">{{ __('search.allCities') }}</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->key }}" @selected($filters['city'] === $city->key)>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="cat">{{ __('search.category') }}</label>
                <select id="cat" class="input" name="cat">
                    <option value="all">{{ __('search.allCategories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->key }}" @selected($filters['cat'] === $category->key)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-blue">{{ __('search.submit') }}</button>
            <a href="{{ lroute('admin.jobs.index') }}" class="btn btn-secondary">{{ __('results.clear') }}</a>
        </div>
    </form>

    {{-- ── The queue ────────────────────────────────────────────────────── --}}
    <section class="adm-panel">

        <div class="adm-tabs">
            @foreach (['pending', 'published', 'rejected', 'draft', 'expired', 'filled', 'all'] as $status)
                <a href="{{ lroute('admin.jobs.index', array_merge(request()->except(['status', 'page']), ['status' => $status])) }}"
                   class="adm-tab {{ $filters['status'] === $status ? 'is-on' : '' }}">
                    {{ __('admin.status.'.$status) }}
                    <span class="adm-tab-n">{{ sar($statusCounts[$status] ?? 0) }}</span>
                </a>
            @endforeach
        </div>

        @if ($jobs->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-briefcase"></i>
                <p style="margin:0">{{ __('admin.queue.empty') }}</p>
            </div>
        @else
            {{--
                One form wraps the table, so the tickboxes in the rows and the
                bar above them are the same submission. The bar only appears
                once something is ticked — a resting queue stays quiet.
            --}}
            <form method="POST" action="{{ lroute('admin.jobs.bulk') }}"
                  x-data="{ picked: [], get n() { return this.picked.length } }">
                @csrf

                <div class="adm-bulk" x-show="n > 0" x-cloak>
                    <span class="adm-bulk-n" x-text="@js(__('admin.queue.selected', ['n' => ':n'])).replace(':n', n)"></span>

                    <button type="submit" name="action" value="approve" class="btn-blue">
                        <i class="ph ph-check"></i>{{ __('admin.queue.approve') }}
                    </button>

                    {{-- A bulk rejection still needs a reason; it is shown to
                         every poster in the selection. --}}
                    <button type="submit" name="action" value="reject" class="btn btn-secondary"
                            @click="if (! $refs.reason.value.trim()) { $refs.reason.focus(); $event.preventDefault() }">
                        <i class="ph ph-x"></i>{{ __('admin.queue.reject') }}
                    </button>

                    <input x-ref="reason" class="input" name="reason" type="text" style="flex:1 1 220px"
                           placeholder="{{ __('admin.listing.rejectPrompt') }}">

                    <button type="submit" name="action" value="delete" class="btn btn-secondary"
                            @click="if (! confirm(@js(__('admin.queue.bulkConfirm')))) $event.preventDefault()">
                        <i class="ph ph-trash"></i>{{ __('admin.queue.delete') }}
                    </button>
                </div>

                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th style="width:1%">
                                    <input type="checkbox" aria-label="{{ __('admin.queue.selectAll') }}"
                                           @change="picked = $event.target.checked
                                               ? Array.from($el.closest('table').querySelectorAll('[name=\'ids[]\']')).map(i => i.value)
                                               : []">
                                </th>
                                <th>{{ __('search.keyword') }}</th>
                                <th>{{ __('admin.listing.employer') }}</th>
                                <th>{{ __('search.city') }}</th>
                                <th>{{ __('search.category') }}</th>
                                <th>{{ __('admin.listing.submitted') }}</th>
                                <th>{{ __('admin.status.all') }}</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($jobs as $job)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="ids[]" value="{{ $job->id }}"
                                               x-model="picked"
                                               aria-label="{{ $job->title }}">
                                    </td>

                                    <td>
                                        <a href="{{ lroute('admin.jobs.show', $job) }}" class="adm-cell-title">
                                            {{ $job->title }}
                                        </a>
                                        <span class="adm-cell-sub">
                                            {{ $job->employmentType->name }}
                                            @if ($job->is_featured) · {{ __('admin.listing.featured') }} @endif
                                            @if ($job->is_urgent) · {{ __('admin.listing.urgent') }} @endif
                                            @if ($job->reports_count > 0)
                                                · <span style="color:var(--color-cta)">
                                                    {{ __('admin.queue.openReports', ['n' => $job->reports_count]) }}
                                                </span>
                                            @endif
                                        </span>
                                    </td>

                                    <td>
                                        {{ $job->company?->name ?? $job->user->name }}
                                        @if ($job->company?->is_verified)
                                            <i class="ph ph-seal-check" style="color:var(--color-accent-700)"></i>
                                        @endif
                                    </td>

                                    <td>{{ $job->city->name }}</td>
                                    <td>{{ $job->category->name }}</td>
                                    <td class="num">{{ $job->created_at->diffForHumans() }}</td>

                                    <td>@include('admin.partials.status-pill', ['status' => $job->status])</td>

                                    <td style="width:1%">
                                        <div class="adm-row-actions">
                                            @if ($job->status !== Job::STATUS_PUBLISHED)
                                                <button type="submit" name="action" value="approve"
                                                        class="btn btn-primary"
                                                        @click="picked = ['{{ $job->id }}']">
                                                    <i class="ph ph-check"></i>{{ __('admin.queue.approve') }}
                                                </button>
                                            @endif

                                            <a href="{{ lroute('admin.jobs.show', $job) }}" class="btn btn-secondary">
                                                <i class="ph ph-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>

            @if ($jobs->hasPages())
                <div style="padding:var(--space-6)">{{ $jobs->links() }}</div>
            @endif
        @endif

    </section>

@endsection
