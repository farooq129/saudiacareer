@extends('layouts.app')

@section('title', __('Page not found'))

@section('content')
    <div class="page-narrow" style="max-width:520px;text-align:center;padding-block:96px">
        <i class="ph ph-compass" style="font-size:38px;color:var(--color-neutral-500)"></i>

        <h3 style="margin:var(--space-6) 0 var(--space-3)">{{ __('Page not found') }}</h3>
        <p class="text-muted" style="margin-bottom:var(--space-8)">
            {{ __('The page you were looking for is no longer here.') }}
        </p>

        <div style="display:flex;gap:var(--space-3);justify-content:center;flex-wrap:wrap">
            <a href="{{ lroute('home') }}" class="btn-blue">{{ __('Back to the home page') }}</a>
            <a href="{{ lroute('jobs.index') }}" class="btn btn-secondary">{{ __('nav.viewAll') }}</a>
        </div>
    </div>
@endsection
