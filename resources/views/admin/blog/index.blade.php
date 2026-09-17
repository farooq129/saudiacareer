@extends('layouts.portal')

@php use App\Models\BlogPost; @endphp

@section('title', __('admin.blog.title'))
@section('heading', __('admin.blog.title'))

@section('content')

    <div class="adm-head-actions">
        <a href="{{ lroute('admin.blog.create') }}" class="btn-blue">
            <i class="ph ph-plus" style="margin-inline-end:6px"></i>{{ __('admin.blog.new') }}
        </a>

        @if (auth()->user()->isAdmin())
            <a href="{{ lroute('admin.blog.sections.index') }}" class="btn btn-secondary">
                {{ __('admin.blog.sectionsLink') }}
            </a>
        @endif

        <a href="{{ lroute('blog.index') }}" class="btn btn-secondary" target="_blank" rel="noopener">
            {{ __('admin.blog.viewLive') }}
        </a>
    </div>

    <form method="GET" action="{{ lroute('admin.blog.index') }}" class="adm-panel" style="padding:var(--space-6)">
        <div class="adm-filters">
            <div class="field field-grow">
                <label for="q">{{ __('admin.blog.search') }}</label>
                <input id="q" class="input" type="search" name="q" value="{{ $filters['q'] }}">
            </div>

            <div class="field">
                <label for="status">{{ __('admin.listing.status') }}</label>
                <select id="status" class="input" name="status">
                    <option value="all" @selected($filters['status'] === 'all')>{{ __('admin.blog.allStatuses') }}</option>
                    <option value="draft" @selected($filters['status'] === 'draft')>{{ __('admin.blog.status.draft') }}</option>
                    <option value="published" @selected($filters['status'] === 'published')>{{ __('admin.blog.status.published') }}</option>
                </select>
            </div>

            <div class="field">
                <label for="section">{{ __('admin.blog.form.section') }}</label>
                <select id="section" class="input" name="section">
                    <option value="all" @selected($filters['section'] === 'all')>{{ __('admin.blog.allSections') }}</option>
                    @foreach ($sections as $item)
                        <option value="{{ $item->key }}" @selected($filters['section'] === $item->key)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-blue">{{ __('search.submit') }}</button>
            <a href="{{ lroute('admin.blog.index') }}" class="btn btn-secondary">{{ __('results.clear') }}</a>
        </div>
    </form>

    <section class="adm-panel">
        @if ($posts->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-newspaper"></i>
                <p style="margin:0">{{ __('admin.blog.empty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.blog.form.headline') }}</th>
                            <th>{{ __('admin.blog.form.section') }}</th>
                            <th>{{ __('admin.blog.author') }}</th>
                            <th>{{ __('admin.listing.status') }}</th>
                            <th>{{ __('admin.blog.published') }}</th>
                            <th>{{ __('admin.blog.views') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>
                                    <a href="{{ lroute('admin.blog.edit', $post) }}" class="adm-cell-title">
                                        {{ $post->title }}
                                    </a>
                                    <span class="adm-cell-sub" dir="ltr">/{{ $post->slug }}</span>
                                </td>
                                <td>{{ $post->category?->name ?? '—' }}</td>
                                <td>{{ $post->byline() ?? '—' }}</td>
                                <td>
                                    @if ($post->isScheduled())
                                        <span class="tag tag-outline">{{ __('admin.blog.status.scheduled') }}</span>
                                    @elseif ($post->status === BlogPost::STATUS_PUBLISHED)
                                        <span class="tag tag-accent">{{ __('admin.blog.status.published') }}</span>
                                    @else
                                        <span class="tag tag-neutral">{{ __('admin.blog.status.draft') }}</span>
                                    @endif

                                    @unless ($post->is_indexable)
                                        <span class="tag tag-neutral">{{ __('admin.blog.form.noindexShort') }}</span>
                                    @endunless
                                </td>
                                <td>{{ $post->published_at?->translatedFormat('j M Y') ?? '—' }}</td>
                                <td>{{ number_format($post->views_count) }}</td>
                                <td style="text-align:end;white-space:nowrap">
                                    <a href="{{ lroute('admin.blog.edit', $post) }}" class="btn btn-secondary btn-sm">
                                        {{ __('admin.blog.edit') }}
                                    </a>
                                    @if ($post->isPublished())
                                        <a href="{{ lroute('blog.show', $post) }}" class="btn btn-secondary btn-sm"
                                           target="_blank" rel="noopener">{{ __('admin.blog.view') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('partials.pagination', ['paginator' => $posts])
        @endif
    </section>

@endsection
