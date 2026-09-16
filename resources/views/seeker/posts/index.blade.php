@extends('layouts.portal')

@php use App\Models\JobSeekerPost; @endphp

@section('title', __('seeker.posts.title'))
@section('heading', __('seeker.posts.title'))

@section('topAction')
    <a href="{{ lroute('seeker.posts.create') }}" class="btn-cta">
        <i class="ph ph-plus"></i>{{ __('seeker.posts.new') }}
    </a>
@endsection

@section('content')

    <section class="adm-panel">
        @if ($posts->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-user-list"></i>
                <p style="margin:0">{{ __('seeker.posts.empty') }}</p>
                <a href="{{ lroute('seeker.posts.create') }}" class="btn-blue">{{ __('seeker.posts.new') }}</a>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('search.keyword') }}</th>
                            <th>{{ __('search.city') }}</th>
                            <th>{{ __('seeker.posts.views') }}</th>
                            <th>{{ __('admin.status.all') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>
                                    <a href="{{ lroute('seeker.posts.edit', $post) }}" class="adm-cell-title">
                                        {{ $post->headline }}
                                    </a>
                                    <span class="adm-cell-sub">{{ $post->category->name }}</span>

                                    @if ($post->status === JobSeekerPost::STATUS_REJECTED && $post->rejection_reason)
                                        <span class="adm-cell-sub" style="color:var(--color-cta)">
                                            {{ $post->rejection_reason }}
                                        </span>
                                    @endif
                                </td>

                                <td>{{ $post->city->name }}</td>
                                <td class="num">{{ sar($post->views_count) }}</td>
                                <td>@include('admin.partials.status-pill', ['status' => $post->status])</td>

                                <td style="width:1%">
                                    <div class="adm-row-actions">
                                        <a href="{{ lroute('seeker.posts.edit', $post) }}" class="btn btn-secondary">
                                            <i class="ph ph-pencil-simple"></i>
                                        </a>

                                        <form method="POST" action="{{ lroute('seeker.posts.destroy', $post) }}"
                                              onsubmit="return confirm(@js(__('seeker.posts.deleteConfirm')))">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-secondary" style="color:var(--color-cta)">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($posts->hasPages())
                <div style="padding:var(--space-6)">{{ $posts->links() }}</div>
            @endif
        @endif
    </section>

@endsection
