@extends('layouts.app')

@php
    use App\Support\Plural;

    /*
     * The heading names what you filtered by, in the order you would say it:
     * the keyword in quotes, then the category, then the city. The quotes are
     * the language's own — guillemets in Arabic, curly doubles in English.
     */
    $bits = [];

    if ($filters['q'] !== '') {
        $bits[] = app()->getLocale() === 'ar'
            ? '«'.$filters['q'].'»'
            : '“'.$filters['q'].'”';
    }

    if ($filters['cat'] !== 'all' && $activeCategory) {
        $bits[] = $activeCategory->name;
    }

    if ($filters['city'] !== 'all' && $activeCity) {
        $bits[] = __('results.in', ['city' => $activeCity->name]);
    }

    $heading = $bits ? implode(' · ', $bits) : __('results.allJobs');
@endphp

@section('title', $heading.' — '.config('board.brand.'.app()->getLocale()))

@section('content')

    <div class="page-results">

        {{-- ── Filter rail ──────────────────────────────────────────────────
             One form. Every control submits it, so the URL always describes
             exactly what is on screen and stays shareable. --}}
        <aside style="min-width:0">
            <form method="GET" action="{{ lroute('jobs.index') }}">

                <div class="rail">
                    <h5>{{ __('results.filter') }}</h5>
                </div>

                <div class="field" style="margin-bottom:var(--space-6)">
                    <label for="f-q">{{ __('search.keyword') }}</label>
                    <input id="f-q" class="input" type="search" name="q"
                           placeholder="{{ __('search.keywordShortPh') }}"
                           value="{{ $filters['q'] }}">
                </div>

                <div class="field" style="margin-bottom:var(--space-6)">
                    <label for="f-city">{{ __('search.city') }}</label>
                    <select id="f-city" class="input" name="city" onchange="this.form.submit()">
                        <option value="all">{{ __('search.allCities') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->key }}" @selected($filters['city'] === $city->key)>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field" style="margin-bottom:var(--space-6)">
                    <label for="f-cat">{{ __('search.category') }}</label>
                    <select id="f-cat" class="input" name="cat" onchange="this.form.submit()">
                        <option value="all">{{ __('search.allCategories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->key }}" @selected($filters['cat'] === $category->key)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:var(--space-6)">
                    <div style="font-size:12px;color:var(--color-neutral-700);margin-bottom:9px">
                        {{ __('search.employmentType') }}
                    </div>

                    <div style="display:flex;flex-direction:column;gap:9px">
                        <label class="radio">
                            <input type="radio" name="type" value="all"
                                   @checked($filters['type'] === 'all') onchange="this.form.submit()">
                            <span class="dot"></span>{{ __('search.anyType') }}
                        </label>

                        @foreach ($employmentTypes as $type)
                            <label class="radio">
                                <input type="radio" name="type" value="{{ $type->key }}"
                                       @checked($filters['type'] === $type->key) onchange="this.form.submit()">
                                <span class="dot"></span>{{ $type->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn-blue btn-wide" style="margin-bottom:var(--space-3)">
                    {{ __('search.submit') }}
                </button>

                <a href="{{ lroute('jobs.index') }}" class="btn btn-secondary btn-block">
                    <i class="ph ph-x" style="font-size:14px"></i>{{ __('results.clear') }}
                </a>
            </form>
        </aside>

        {{-- ── Results ──────────────────────────────────────────────────── --}}
        <main style="min-width:0">

            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap;margin-bottom:var(--space-6)">
                <h3 style="margin:0;font-size:26px">{{ $heading }}</h3>
                @if ($jobs->total() > 0)
                    <span class="text-muted" style="font-size:13px">
                        {{ Plural::of($jobs->total(), 'results.count') }}
                    </span>
                @endif
            </div>

            @if ($jobs->isEmpty())
                <div style="display:flex;flex-direction:column;align-items:center;gap:var(--space-3);padding:64px 0;text-align:center">
                    <i class="ph ph-briefcase" style="font-size:32px;color:var(--color-neutral-500)"></i>
                    <p class="text-muted" style="margin:0">{{ __('results.empty') }}</p>
                </div>
            @else
                <div class="list-panel">
                    @foreach ($jobs as $job)
                        @include('partials.job-row', ['job' => $job, 'variant' => 'result'])
                    @endforeach
                </div>

                @if ($jobs->hasPages())
                    <div style="margin-top:var(--space-8)">{{ $jobs->links() }}</div>
                @endif
            @endif

        </main>
    </div>

@endsection
