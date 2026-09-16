@extends('layouts.app')

@section('title', __('nav.saved').' — '.config('board.brand.'.app()->getLocale()))

@section('content')

    <div class="page-narrow">

        <div class="rail">
            <h5>{{ __('nav.saved') }}</h5>
            @if ($jobs->isNotEmpty())
                <span class="rail-note">{{ $jobs->count() }}</span>
            @endif
        </div>

        @if ($jobs->isEmpty())
            <div style="display:flex;flex-direction:column;align-items:center;gap:var(--space-4);padding:72px 0;text-align:center">
                <i class="ph ph-bookmark-simple" style="font-size:32px;color:var(--color-neutral-500)"></i>
                <p class="text-muted" style="margin:0;max-width:44ch">{{ __('results.empty') }}</p>
                <a href="{{ lroute('jobs.index') }}" class="btn-blue">{{ __('nav.viewAll') }}</a>
            </div>
        @else
            <div class="list-panel">
                @foreach ($jobs as $job)
                    @include('partials.job-row', ['job' => $job, 'variant' => 'result'])
                @endforeach
            </div>

            @guest
                {{-- Saving works signed out, but the list lives in this browser's
                     session until there is an account to attach it to. Say so
                     rather than letting it vanish silently. --}}
                <p class="text-muted" style="margin-top:var(--space-6);font-size:12.5px;text-align:center">
                    {{ __('Sign in to keep these ads on your account.') }}
                    <a href="{{ lroute('login') }}">{{ __('nav.signIn') }}</a>
                </p>
            @endguest
        @endif

    </div>

@endsection
