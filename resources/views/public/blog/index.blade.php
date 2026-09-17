@extends('layouts.app')

@php use App\Support\Locale; @endphp

@section('title', $heading.' — '.config('board.brand.'.Locale::current()))
@section('description', $blurb)

@push('head')
    {{-- The feed is a Blog in schema terms and each card a BlogPosting. Built
         in the controller: '@context' written inline here would be read as a
         Blade directive and compiled away. --}}
    <script type="application/ld+json">{!! App\Support\StructuredData::json($jsonLd) !!}</script>
@endpush

@section('content')

    <div class="page-narrow">

        <header class="blog-head">
            <h1>{{ $heading }}</h1>
            <p class="text-muted">{{ $blurb }}</p>
        </header>

        {{-- Sections double as the filter rail. Each is a real link to its own
             indexable URL, not a query-string toggle. --}}
        <nav class="blog-sections" aria-label="{{ __('blog.sections') }}">
            <a href="{{ lroute('blog.index') }}"
               class="blog-chip {{ $section ? '' : 'is-on' }}">{{ __('blog.all') }}</a>

            @foreach ($sections as $item)
                <a href="{{ lroute('blog.section', $item) }}"
                   class="blog-chip {{ $section?->id === $item->id ? 'is-on' : '' }}">
                    {{ $item->name }}
                    @if ($item->posts_count)
                        <span class="blog-chip-n">{{ $item->posts_count }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ lroute('blog.index') }}" class="blog-search">
            <label for="q" class="sr-only">{{ __('blog.searchLabel') }}</label>
            <input id="q" class="input" type="search" name="q" value="{{ $filters['q'] }}"
                   placeholder="{{ __('blog.searchPlaceholder') }}">
            <button type="submit" class="btn-blue">{{ __('search.submit') }}</button>
        </form>

        @if ($lead)
            <a href="{{ lroute('blog.show', $lead) }}" class="card elev-sm blog-lead">
                <span class="blog-lead-art">
                    @if ($lead->image_path)
                        <img src="{{ asset($lead->image_path) }}" alt="{{ $lead->imageAlt() }}">
                    @else
                        <img src="{{ asset('images/main_logo.png') }}" alt="" class="card-fallback-logo">
                    @endif
                </span>

                <span class="blog-lead-body">
                    <span class="blog-kicker">
                        {{ $lead->category?->name ?? __('blog.featured') }}
                    </span>
                    <span class="blog-lead-title">{{ $lead->title }}</span>
                    @if ($lead->excerpt)
                        <span class="card-body" style="line-height:1.75">{{ $lead->excerpt }}</span>
                    @endif
                    <span class="card-meta blog-card-meta">
                        <time datetime="{{ $lead->published_at?->toDateString() }}">
                            {{ $lead->published_at?->translatedFormat('j F Y') }}
                        </time>
                        <span>·</span>
                        <span>{{ __('blog.readingTime', ['n' => $lead->readingMinutes()]) }}</span>
                    </span>
                </span>
            </a>
        @endif

        @if ($posts->isEmpty())
            <div class="blog-empty">
                <i class="ph ph-newspaper"></i>
                <p>{{ __('blog.empty') }}</p>
            </div>
        @else
            <div class="blog-grid">
                @foreach ($posts as $post)
                    @continue ($lead && $lead->id === $post->id)
                    @include('partials.blog-card')
                @endforeach
            </div>

            @include('partials.pagination', ['paginator' => $posts])
        @endif

    </div>

@endsection
