@extends('layouts.app')

@php
    use App\Support\Locale;

    $crumbIcon = Locale::isRtl() ? 'ph-caret-left' : 'ph-caret-right';
@endphp

@section('title', $post->metaTitle())
@section('description', $post->metaDescription())

{{-- An editor can point the canonical elsewhere when an article is a
     republished copy of something that lives on another site. --}}
@section('canonical', $post->canonical_url ?: url()->current())

@push('head')
    {{-- An editor can keep a thin or duplicated page out of the index without
         unpublishing it — a seasonal repost, a thank-you page. --}}
    @unless ($post->is_indexable)
        <meta name="robots" content="noindex, follow">
    @endunless

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $post->metaTitle() }}">
    <meta property="og:description" content="{{ $post->metaDescription() }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:locale" content="{{ Locale::current() === 'ar' ? 'ar_SA' : 'en_US' }}">
    <meta property="article:published_time" content="{{ $post->published_at?->toAtomString() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at?->toAtomString() }}">
    @if ($post->category)
        <meta property="article:section" content="{{ $post->category->name }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $post->metaTitle() }}">
    <meta name="twitter:description" content="{{ $post->metaDescription() }}">
    <meta name="twitter:image" content="{{ $image }}">

    {{-- BlogPosting plus the breadcrumb trail, both built in the controller:
         '@context' written inline here would be read as a Blade directive and
         compiled away, leaving a script block full of PHP source. --}}
    <script type="application/ld+json">{!! App\Support\StructuredData::json($jsonLd) !!}</script>
    <script type="application/ld+json">{!! App\Support\StructuredData::json($breadcrumbsLd) !!}</script>
@endpush

@section('content')

    <div class="page-detail-wrap">

        <nav class="blog-crumbs" aria-label="{{ __('detail.home') }}">
            <a href="{{ lroute('home') }}">{{ __('detail.home') }}</a>
            <i class="ph {{ $crumbIcon }}"></i>
            <a href="{{ lroute('blog.index') }}">{{ __('blog.title') }}</a>
            @if ($post->category)
                <i class="ph {{ $crumbIcon }}"></i>
                <a href="{{ lroute('blog.section', $post->category) }}">{{ $post->category->name }}</a>
            @endif
        </nav>

        <article class="blog-article">

            <header>
                @if ($post->category)
                    <span class="blog-kicker">{{ $post->category->name }}</span>
                @endif

                <h1 class="blog-title">{{ $post->title }}</h1>

                @if ($post->excerpt)
                    <p class="blog-standfirst">{{ $post->excerpt }}</p>
                @endif

                <div class="blog-byline">
                    @if ($post->byline())
                        <span>{{ __('blog.by', ['name' => $post->byline()]) }}</span>
                        <span>·</span>
                    @endif
                    <time datetime="{{ $post->published_at?->toDateString() }}">
                        {{ $post->published_at?->translatedFormat('j F Y') }}
                    </time>
                    <span>·</span>
                    <span>{{ __('blog.readingTime', ['n' => $post->readingMinutes()]) }}</span>
                </div>
            </header>

            @if ($post->image_path)
                <figure class="blog-hero">
                    <img src="{{ asset($post->image_path) }}" alt="{{ $post->imageAlt() }}"
                         width="1200" height="630">
                </figure>
            @endif

            {{-- The body is a list of paragraphs, each escaped by Blade. There
                 is no path from the editor to raw markup on this page. --}}
            <div class="blog-body">
                @foreach (($post->body ?? []) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

        </article>

        @if ($related->isNotEmpty())
            <section class="blog-related">
                <div class="rail">
                    <h5>{{ __('blog.related') }}</h5>
                </div>

                <div class="blog-grid">
                    @foreach ($related as $item)
                        @include('partials.blog-card', ['post' => $item, 'compact' => true])
                    @endforeach
                </div>
            </section>
        @endif

    </div>

@endsection
