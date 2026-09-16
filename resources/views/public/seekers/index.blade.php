@extends('layouts.app')

@php use App\Support\Plural; @endphp

@section('title', __('home.seekerPosts').' — '.config('board.brand.'.app()->getLocale()))

@section('content')

    {{-- The same two-column shape as the results screen: this is the employer's
         side of the same board, so it should not feel like a different site. --}}
    <div class="page-results">

        <aside style="min-width:0">
            <form method="GET" action="{{ lroute('seekers.index') }}">

                <div class="rail">
                    <h5>{{ __('results.filter') }}</h5>
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

                <a href="{{ lroute('seekers.index') }}" class="btn btn-secondary btn-block">
                    <i class="ph ph-x" style="font-size:14px"></i>{{ __('results.clear') }}
                </a>
            </form>
        </aside>

        <main style="min-width:0">

            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:var(--space-3);flex-wrap:wrap;margin-bottom:var(--space-6)">
                <h3 style="margin:0;font-size:26px">{{ __('home.seekerPosts') }}</h3>
                <span class="text-muted" style="font-size:13px">
                    <i class="ph ph-sort-descending" style="margin-inline-end:4px"></i>{{ __('home.sortedNewest') }}
                </span>
            </div>

            @if ($posts->isEmpty())
                <div style="display:flex;flex-direction:column;align-items:center;gap:var(--space-3);padding:64px 0;text-align:center">
                    <i class="ph ph-users-three" style="font-size:32px;color:var(--color-neutral-500)"></i>
                    <p class="text-muted" style="margin:0">{{ __('results.empty') }}</p>
                </div>
            @else
                <div class="list-panel">
                    @foreach ($posts as $post)
                        @include('partials.seeker-row', ['post' => $post])
                    @endforeach
                </div>

                <div class="text-muted" style="margin-top:var(--space-4);font-size:12px;text-align:center">
                    {{ __('home.seekerCount', ['n' => sar($posts->total())]) }}
                </div>

                @if ($posts->hasPages())
                    <div style="margin-top:var(--space-8)">{{ $posts->links() }}</div>
                @endif
            @endif

        </main>
    </div>

@endsection
