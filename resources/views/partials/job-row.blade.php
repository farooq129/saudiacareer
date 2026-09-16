@php
    use App\Support\Plural;

    // The canvas stores "posted 22 minutes ago" rather than a date, because a
    // daily board is read for freshness. Derive the same figure from the
    // timestamp we actually store.
    $postedMinutes = (int) ($job->published_at?->diffInMinutes(now()) ?? 0);

    // Three row shapes, one component. 'latest' is the home feed (no monogram,
    // excerpt on its own line), 'result' is the results list (monogram, salary),
    // 'compact' is the similar-jobs rail on a detail page.
    $variant ??= 'result';
@endphp

@if ($variant === 'latest')
    <a href="{{ lroute('jobs.show', $job) }}" class="list-row"
       style="grid-template-columns:minmax(0,1fr) auto;padding:26px 18px">
        <span style="min-width:0">
            <span style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span class="list-row-title">{{ $job->title }}</span>
            </span>

            @if ($job->excerpt)
                <span class="list-row-sub">{{ $job->excerpt }}</span>
            @endif

            <span class="list-row-meta">
                <span style="color:var(--color-text)">{{ $job->category->name }}</span>
                <span><i class="ph ph-map-pin" style="margin-inline-end:4px"></i>{{ $job->city->name }}</span>
            </span>
        </span>

        <span class="text-muted" style="text-align:end;white-space:nowrap;font-size:12px">
            {{ Plural::ago($postedMinutes) }}
        </span>
    </a>

@elseif ($variant === 'compact')
    <a href="{{ lroute('jobs.show', $job) }}" class="list-row"
       style="grid-template-columns:auto minmax(0,1fr) auto;gap:var(--space-4);padding:15px 18px">
        <span class="mono mono-sm">{{ $job->company?->monogram() ?? '؟' }}</span>

        <span style="min-width:0">
            <span style="display:block;font-family:var(--font-heading);font-weight:500;font-size:15px">{{ $job->title }}</span>
            <span class="text-muted" style="display:block;font-size:13px">
                {{ $job->company?->name }} · {{ $job->city->name }}
            </span>
        </span>

        <span class="text-muted" style="font-size:12px;white-space:nowrap">
            {{ Plural::ago($postedMinutes) }}
        </span>
    </a>

@else
    <a href="{{ lroute('jobs.show', $job) }}" class="list-row"
       style="grid-template-columns:auto minmax(0,1fr) auto;padding:17px 18px">
        <span class="mono">{{ $job->company?->monogram() ?? '؟' }}</span>

        <span style="min-width:0">
            <span style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="font-family:var(--font-heading);font-weight:500;font-size:16px">{{ $job->title }}</span>

                @if ($job->company?->is_verified)
                    <span class="tag tag-accent">
                        <i class="ph ph-seal-check" style="margin-inline-end:4px"></i>{{ __('tag.verified') }}
                    </span>
                @endif

                @if ($job->is_urgent)
                    <span class="tag tag-outline">
                        <i class="ph ph-lightning" style="margin-inline-end:4px"></i>{{ __('tag.urgent') }}
                    </span>
                @endif

                @if ($job->transfer_available)
                    <span class="tag tag-neutral">{{ __('tag.transfer') }}</span>
                @endif
            </span>

            <span class="list-row-meta">
                <span>{{ $job->company?->name }}</span>
                <span><i class="ph ph-map-pin" style="margin-inline-end:4px"></i>{{ $job->city->name }}</span>
                <span><i class="ph ph-clock-user" style="margin-inline-end:4px"></i>{{ $job->employmentType->name }}</span>
                <span>{{ $job->category->name }}</span>
            </span>
        </span>

        <span style="text-align:end;white-space:nowrap">
            @if ($job->salary_min)
                <span style="display:block;font-size:14px;color:var(--color-accent-700)">
                    @if ($job->salary_max)
                        {{ __('units.salaryRange', ['min' => sar($job->salary_min), 'max' => sar($job->salary_max)]) }}
                    @else
                        {{ __('units.salaryOne', ['n' => sar($job->salary_min)]) }}
                    @endif
                </span>
            @endif
            <span class="text-muted" style="display:block;font-size:12px">{{ Plural::ago($postedMinutes) }}</span>
        </span>
    </a>
@endif
