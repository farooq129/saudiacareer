@php
    /*
     * One article card, used by the blog index, the section pages and the
     * related rail at the foot of an article. $compact drops the excerpt for
     * the rail, where three cards share a row.
     */
    $compact = $compact ?? false;
@endphp

<a href="{{ lroute('blog.show', $post) }}" class="card elev-sm blog-card">
    <span class="blog-card-art">
        @if ($post->image_path)
            <img src="{{ asset($post->image_path) }}" alt="{{ $post->imageAlt() }}" loading="lazy">
        @else
            {{-- No photo: the site lockup stands in, exactly as on a job card,
                 so a card is never a blank rectangle. --}}
            <img src="{{ asset('images/main_logo.png') }}" alt="" class="card-fallback-logo" loading="lazy">
        @endif
    </span>

    <span class="blog-card-body">
        @if ($post->category)
            <span class="blog-kicker">{{ $post->category->name }}</span>
        @endif

        <span class="card-title" style="line-height:1.4">{{ $post->title }}</span>

        @unless ($compact)
            @if ($post->excerpt)
                <span class="card-body" style="font-size:13px;line-height:1.7">{{ $post->excerpt }}</span>
            @endif
        @endunless

        <span class="card-meta blog-card-meta">
            <time datetime="{{ $post->published_at?->toDateString() }}">
                {{ $post->published_at?->translatedFormat('j F Y') }}
            </time>
            <span>·</span>
            <span>{{ __('blog.readingTime', ['n' => $post->readingMinutes()]) }}</span>
        </span>
    </span>
</a>
