@php
    use App\Support\Plural;

    $postedMinutes = (int) ($post->published_at?->diffInMinutes(now()) ?? 0);
@endphp

{{-- The mirror of a job row, and deliberately the same shape: the two feeds sit
     behind one tab bar, so they have to read as two sides of one board. --}}
<a href="{{ lroute('seekers.show', $post) }}" class="list-row"
   style="grid-template-columns:minmax(0,1fr) auto;padding:26px 18px">
    <span style="min-width:0">
        <span style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="list-row-title">{{ $post->headline }}</span>

            @if ($post->transfer_available)
                <span class="tag tag-neutral">{{ __('tag.transfer') }}</span>
            @endif
        </span>

        @if ($post->pitch)
            <span class="list-row-sub">{{ $post->pitch }}</span>
        @endif

        <span class="list-row-meta">
            <span style="color:var(--color-text)">{{ $post->category->name }}</span>
            <span><i class="ph ph-map-pin" style="margin-inline-end:4px"></i>{{ $post->city->name }}</span>
            @if ($post->experience)
                <span>{{ $post->experience }}</span>
            @endif
        </span>
    </span>

    <span class="text-muted" style="text-align:end;white-space:nowrap;font-size:12px">
        {{ Plural::ago($postedMinutes) }}
    </span>
</a>
