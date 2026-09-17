@extends('layouts.app')

@php
    use App\Support\Locale;
    use App\Support\Plural;

    $isAr = Locale::isRtl();
    $arrowIcon = $isAr ? 'ph-arrow-left' : 'ph-arrow-right';
@endphp

@section('title', __('home.title').' — '.config('board.brand.'.app()->getLocale()))
@section('description', __('home.lead'))

@section('content')

    {{-- ── Hero ──────────────────────────────────────────────────────────────
         The photograph runs the full width of the viewport while the content
         stays on the same 1240px measure as the rest of the page. --}}
    <section class="hero">
        <div class="hero-media" style="background-image:url({{ asset('images/bg_img_2.png') }})"></div>

        <div class="hero-inner">
            <h1 style="font-size:clamp(30px,4.2vw,46px);max-width:20ch;margin-bottom:var(--space-4);line-height:1.28">
                {{ __('home.title') }}
            </h1>
            <p style="max-width:58ch;font-size:16px;color:var(--color-text)">{{ __('home.lead') }}</p>

            <form method="GET" action="{{ lroute('jobs.index') }}" class="hero-search" role="search"
                  style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:var(--space-3);align-items:end;margin-top:var(--space-8);padding:var(--space-6);border-radius:var(--radius-sm)">

                <div class="field hero-field" style="grid-column:span 2;min-width:0">
                    <img src="{{ asset('icons/ui/search.svg') }}" alt="" width="20" height="20">
                    <label class="sr-only" for="hero-q">{{ __('search.keyword') }}</label>
                    <input id="hero-q" class="input" type="search" name="q"
                           placeholder="{{ __('search.keywordPh') }}" value="{{ request('q') }}">
                </div>

                <div class="field hero-field" style="min-width:0">
                    <img src="{{ asset('icons/ui/location.svg') }}" alt="" width="20" height="20">
                    <label class="sr-only" for="hero-city">{{ __('search.city') }}</label>
                    <select id="hero-city" class="input" name="city" style="padding-inline-start:38px">
                        <option value="all">{{ __('search.allCities') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->key }}">{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="min-width:0">
                    <label class="sr-only" for="hero-cat">{{ __('search.category') }}</label>
                    <select id="hero-cat" class="input" name="cat">
                        <option value="all">{{ __('search.allCategories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->key }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-blue" style="min-height:36px">{{ __('search.submit') }}</button>
            </form>

            <div style="display:flex;flex-wrap:wrap;gap:var(--space-3) var(--space-6);margin-top:var(--space-4);align-items:center">
                <span style="display:flex;align-items:center;gap:5px;font-size:12px;color:var(--color-text)">
                    <i class="ph ph-trend-up"></i>{{ __('home.trending') }}
                </span>
                @foreach (__('trending') as $term)
                    <a href="{{ lroute('jobs.index', ['q' => $term]) }}" class="trend-link">{{ $term }}</a>
                @endforeach
            </div>
        </div>
    </section>

    <div class="page-home">
        <main style="min-width:0">

            {{-- ── Browse by category ───────────────────────────────────── --}}
            <div class="rail">
                <h5>{{ __('home.browseByCat') }}</h5>
                <span class="rail-note">{{ __('home.catCount', ['n' => $categories->count()]) }}</span>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(196px,1fr));gap:var(--space-3);margin-bottom:56px">
                @foreach ($categories as $category)
                    <a href="{{ lroute('jobs.index', ['cat' => $category->key]) }}" class="cat-tile">
                        <span style="display:grid;place-items:center;flex:none;width:52px;height:52px;color:var(--color-accent-800);font-size:40px">
                            @if ($category->svg_path)
                                <img src="{{ asset($category->svg_path) }}" alt="" width="48" height="48" style="display:block">
                            @else
                                <i class="ph {{ $category->icon }}"></i>
                            @endif
                        </span>
                        <span style="min-width:0">
                            <span style="display:block;font-family:var(--font-heading);font-weight:700;font-size:14px;line-height:1.35;color:var(--color-text)">
                                {{ $category->name }}
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- ── Featured ─────────────────────────────────────────────────
                 A listing with its own photo wins; otherwise the category
                 gradient stands in and carries the site lockup, so every card
                 shows an image either way and an ad with no art still reads as
                 ours rather than as a hole in the grid. --}}
            @if ($featured->isNotEmpty())
                <div style="margin-bottom:56px">
                    <div class="rail">
                        <h5>{{ __('home.featuredToday') }}</h5>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(236px,1fr));gap:var(--space-4)">
                        @foreach ($featured as $job)
                            @php
                                $art = $job->category->art();
                                $postedMinutes = (int) ($job->published_at?->diffInMinutes(now()) ?? 0);
                            @endphp

                            <a href="{{ lroute('jobs.show', $job) }}" class="card elev-sm feature-card"
                               style="text-decoration:none;color:inherit;gap:0;padding:0;border-radius:var(--radius-sm);overflow:hidden">
                                <div style="flex:none;height:132px;display:grid;place-items:center;background:
                                    @if ($job->image_path) url({{ asset($job->image_path) }}) center/cover no-repeat
                                    @else linear-gradient(135deg, {{ $art['from'] }}, {{ $art['to'] }})
                                    @endif">
                                    @unless ($job->image_path)
                                        <img src="{{ asset('images/main_logo.png') }}" alt=""
                                             class="card-fallback-logo" width="1965" height="438">
                                    @endunless
                                </div>

                                <div style="flex:1;display:flex;flex-direction:column;gap:var(--space-3);padding:var(--space-6)">
                                    <div class="card-title" style="line-height:1.4">{{ $job->title }}</div>
                                    <p class="card-body" style="font-size:13px;line-height:1.65">{{ $job->excerpt }}</p>
                                    <div class="card-meta" style="margin-top:var(--space-2)">
                                        <i class="ph ph-clock"></i>{{ Plural::ago($postedMinutes) }}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── The two sides of the board ───────────────────────────────
                 A tab bar rather than the rail every other section uses: a rail
                 labels one thing, a tab bar says there is another thing behind
                 it. Both feeds are already on the page, so the switch is
                 instant and costs no request. --}}
            <div x-data="{ tab: 'jobs' }">
                <div class="tabs" role="tablist">
                    <button type="button" class="tab" :class="tab === 'jobs' && 'is-on'" role="tab"
                            :aria-selected="(tab === 'jobs').toString()" @click="tab = 'jobs'">
                        {{ __('home.latestJobs') }}
                    </button>
                    <button type="button" class="tab" :class="tab === 'seekers' && 'is-on'" role="tab"
                            :aria-selected="(tab === 'seekers').toString()" @click="tab = 'seekers'">
                        {{ __('home.seekerPosts') }}
                    </button>
                    <span class="tabs-note">
                        <i class="ph ph-sort-descending"></i>{{ __('home.sortedNewest') }}
                    </span>
                </div>

                <div x-show="tab === 'jobs'">
                    @if ($latest->isEmpty())
                        <div style="display:flex;flex-direction:column;align-items:center;gap:var(--space-3);padding:64px 0;text-align:center">
                            <i class="ph ph-briefcase" style="font-size:32px;color:var(--color-neutral-500)"></i>
                            <p class="text-muted" style="margin:0">{{ __('results.empty') }}</p>
                        </div>
                    @else
                        <div class="list-panel" style="border-radius:var(--radius-sm)">
                            @foreach ($latest as $job)
                                @include('partials.job-row', ['job' => $job, 'variant' => 'latest'])
                            @endforeach
                        </div>

                        <a href="{{ lroute('jobs.index') }}" class="btn btn-secondary btn-block"
                           style="margin-top:var(--space-6);min-height:40px">
                            {{ __('home.viewAllJobs', ['n' => sar($totalJobs)]) }}
                            <i class="ph {{ $arrowIcon }}" style="margin-inline-start:6px"></i>
                        </a>
                    @endif
                </div>

                <div x-show="tab === 'seekers'" x-cloak>
                    @if ($seekerPosts->isEmpty())
                        <div style="display:flex;flex-direction:column;align-items:center;gap:var(--space-3);padding:64px 0;text-align:center">
                            <i class="ph ph-users-three" style="font-size:32px;color:var(--color-neutral-500)"></i>
                            <p class="text-muted" style="margin:0">{{ __('results.empty') }}</p>
                        </div>
                    @else
                        <div class="list-panel" style="border-radius:var(--radius-sm)">
                            @foreach ($seekerPosts as $post)
                                @include('partials.seeker-row', ['post' => $post])
                            @endforeach
                        </div>

                        <div class="text-muted" style="margin-top:var(--space-4);font-size:12px;text-align:center">
                            {{ __('home.seekerCount', ['n' => sar($totalSeekers)]) }}
                        </div>
                    @endif
                </div>
            </div>

        </main>

        <aside style="min-width:0;display:flex;flex-direction:column;gap:44px">

            <div>
                <div class="rail" style="margin-bottom:var(--space-4)">
                    <h5>{{ __('home.jobsByCity') }}</h5>
                </div>

                <div style="display:flex;flex-direction:column">
                    @foreach ($cities as $city)
                        <a href="{{ lroute('jobs.index', ['city' => $city->key]) }}" class="city-row">
                            <span>{{ $city->name }}</span>
                            <span class="text-muted" style="font-size:12px;font-variant-numeric:tabular-nums">
                                {{ sar($city->jobs_count) }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card elev-sm" style="padding:var(--space-6);gap:var(--space-4);border-radius:var(--radius-sm)">
                <div class="card-title" style="font-size:18px;line-height:1.45;color:var(--color-brand)">
                    {{ __('promo.title') }}
                </div>
                <a href="{{ lroute('register', ['as' => 'employer']) }}" class="btn-blue btn-wide">
                    {{ __('promo.cta') }}
                </a>
            </div>

        </aside>
    </div>

@endsection
