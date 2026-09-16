@extends('layouts.portal')

@php
    use App\Models\JobSeekerPost;
    use App\Support\Phone;
@endphp

@section('title', $post->headline)
@section('heading', $post->headline)

@section('content')

    <div class="adm-detail">

        <div>
            <div class="adm-block">
                <h5>{{ __('admin.listing.overview') }}</h5>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('admin.status.all') }}</dt>
                        <dd>@include('admin.partials.status-pill', ['status' => $post->status])</dd>
                    </div>
                    <div>
                        <dt>{{ __('search.city') }}</dt>
                        <dd>{{ $post->city->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('search.category') }}</dt>
                        <dd>{{ $post->category->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('detail.experienceLabel') }}</dt>
                        <dd>{{ $post->experience ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('tag.transfer') }}</dt>
                        <dd>{{ $post->transfer_available ? '✓' : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.listing.submitted') }}</dt>
                        <dd>{{ $post->created_at->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.listing.published') }}</dt>
                        <dd>{{ $post->published_at?->isoFormat('D MMM YYYY') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.listing.views') }}</dt>
                        <dd class="num">{{ sar($post->views_count) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.listing.content') }}</h5>

                @foreach ([
                    'admin.listing.poster' => 'display_name',
                    'search.keyword' => 'headline',
                    'admin.listing.pitch' => 'pitch',
                ] as $label => $base)
                    <span class="adm-lang-label" style="display:block;margin-bottom:var(--space-2)">{{ __($label) }}</span>

                    <div class="adm-bilingual" style="margin-bottom:var(--space-8)">
                        @foreach (['ar' => 'admin.listing.arabic', 'en' => 'admin.listing.english'] as $lang => $langLabel)
                            <div>
                                <span class="adm-lang-label">{{ __($langLabel) }}</span>
                                @if ($post->missingTranslation($base, $lang))
                                    <p class="adm-missing">{{ __('admin.listing.missing') }}</p>
                                @else
                                    <p style="margin:0">{{ $post->rawTranslation($base, $lang) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <aside>
            <div class="adm-block" x-data="{ rejecting: false, unpublishing: false }">
                <h5>{{ __('admin.listing.moderation') }}</h5>

                <div class="adm-actions">
                    @if ($post->status !== JobSeekerPost::STATUS_PUBLISHED)
                        <form method="POST" action="{{ lroute('admin.seekers.approve', $post) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-blue btn-wide">
                                <i class="ph ph-check"></i>{{ __('admin.queue.approve') }}
                            </button>
                        </form>
                    @endif

                    @if ($post->status !== JobSeekerPost::STATUS_REJECTED)
                        <button type="button" class="btn btn-secondary btn-block" @click="rejecting = ! rejecting">
                            <i class="ph ph-x"></i>{{ __('admin.queue.reject') }}
                        </button>

                        <form method="POST" action="{{ lroute('admin.seekers.reject', $post) }}"
                              x-show="rejecting" x-cloak style="margin-top:var(--space-2)">
                            @csrf @method('PATCH')
                            <div class="field">
                                <label for="reject-reason">{{ __('admin.listing.rejectPrompt') }}</label>
                                <textarea id="reject-reason" class="input" name="reason" rows="3" required minlength="5"></textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block">{{ __('admin.queue.reject') }}</button>
                        </form>
                    @endif

                    @if ($post->status === JobSeekerPost::STATUS_PUBLISHED)
                        <button type="button" class="btn btn-secondary btn-block" @click="unpublishing = ! unpublishing">
                            <i class="ph ph-eye-slash"></i>{{ __('admin.listing.unpublish') }}
                        </button>

                        <form method="POST" action="{{ lroute('admin.seekers.unpublish', $post) }}"
                              x-show="unpublishing" x-cloak style="margin-top:var(--space-2)">
                            @csrf @method('PATCH')
                            <div class="field">
                                <label for="unpublish-note">{{ __('admin.listing.unpublishNote') }}</label>
                                <textarea id="unpublish-note" class="input" name="reason" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block">{{ __('admin.listing.unpublish') }}</button>
                        </form>
                    @endif

                    <div class="hr" style="margin:var(--space-3) 0"></div>

                    <a href="{{ lroute('seekers.show', $post) }}" target="_blank" rel="noopener"
                       class="btn btn-secondary btn-block">
                        <i class="ph ph-arrow-square-out"></i>{{ __('admin.listing.viewPublic') }}
                    </a>

                    <form method="POST" action="{{ lroute('admin.seekers.destroy', $post) }}"
                          onsubmit="return confirm(@js(__('admin.listing.deleteConfirm')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary btn-block" style="color:var(--color-cta)">
                            <i class="ph ph-trash"></i>{{ __('admin.queue.delete') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.listing.moderation') }}</h5>

                @if ($post->reviewed_at)
                    <p style="margin:0;font-size:13px">
                        {{ __('admin.listing.reviewedBy', [
                            'name' => $post->reviewer?->name ?? '—',
                            'date' => $post->reviewed_at->isoFormat('D MMM YYYY, HH:mm'),
                        ]) }}
                    </p>
                @else
                    <p class="text-muted" style="margin:0;font-size:13px">{{ __('admin.listing.neverReviewed') }}</p>
                @endif

                @if ($post->rejection_reason)
                    <div style="margin-top:var(--space-4)">
                        <span class="adm-lang-label">{{ __('admin.listing.rejectionReason') }}</span>
                        <p style="margin:0;font-size:13px">{{ $post->rejection_reason }}</p>
                    </div>
                @endif
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.listing.contact') }}</h5>

                <p style="margin:0 0 var(--space-3)">
                    <span class="adm-cell-title">{{ $post->user->name }}</span>
                    <span class="adm-cell-sub" dir="ltr">{{ Phone::format($post->user->phone) }}</span>
                </p>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('contactDialog.whatsapp') }}</dt>
                        <dd dir="ltr">{{ $post->whatsapp ? Phone::format($post->whatsapp) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('contactDialog.email') }}</dt>
                        <dd dir="ltr" style="word-break:break-all">{{ $post->email ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>

@endsection
