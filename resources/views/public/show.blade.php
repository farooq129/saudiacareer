@extends('layouts.app')

@php
    use App\Support\Locale;
    use App\Support\Phone;

    $isAr = Locale::isRtl();
    $arrowIcon = $isAr ? 'ph-arrow-left' : 'ph-arrow-right';
    $crumbIcon = $isAr ? 'ph-caret-left' : 'ph-caret-right';
@endphp

@section('title', $job->company ? $job->title.' — '.$job->company->name : $job->title)
{{-- Never pass a null here: Blade reads a null second argument as the block
     form of @section and opens an output buffer it never closes. --}}
@section('description', $job->excerpt ?: __('footer.tagline'))

@section('content')

    <div class="page-detail-wrap">

        <nav style="display:flex;gap:var(--space-2);align-items:center;font-size:12px;margin-bottom:var(--space-8)"
             aria-label="{{ __('detail.home') }}">
            <a href="{{ lroute('home') }}">{{ __('detail.home') }}</a>
            <i class="ph {{ $crumbIcon }}" style="color:var(--color-neutral-600);font-size:11px"></i>
            <a href="{{ lroute('jobs.index', ['cat' => $job->category->key]) }}">{{ $job->category->name }}</a>
            <i class="ph {{ $crumbIcon }}" style="color:var(--color-neutral-600);font-size:11px"></i>
            <span class="text-muted">{{ $job->city->name }}</span>
        </nav>

        <div class="page-detail">
            <main style="min-width:0">

                @if ($job->is_urgent || $job->transfer_available)
                    <div style="display:flex;gap:var(--space-2);flex-wrap:wrap;margin-bottom:var(--space-4)">
                        @if ($job->is_urgent)
                            <span class="tag tag-outline">
                                <i class="ph ph-lightning" style="margin-inline-end:4px"></i>{{ __('detail.urgentHiring') }}
                            </span>
                        @endif
                        @if ($job->transfer_available)
                            <span class="tag tag-neutral">{{ __('tag.transfer') }}</span>
                        @endif
                    </div>
                @endif

                <h1 style="font-size:clamp(25px,3.4vw,37px);margin-bottom:var(--space-4);line-height:1.3">
                    {{ $job->title }}
                </h1>

                <div style="display:flex;align-items:center;gap:var(--space-4);flex-wrap:wrap;font-size:14px">
                    <span style="display:flex;align-items:center;gap:9px">
                        <span class="mono mono-sm" style="background:var(--color-surface);box-shadow:var(--shadow-sm)">
                            @include('partials.company-icon')
                        </span>
                        {{ $job->company?->name }}@if ($job->company?->is_verified)@include('partials.verified-badge')@endif
                    </span>

                    <span class="text-muted" style="display:flex;align-items:center;gap:6px">
                        <i class="ph ph-map-pin"></i>{{ $job->city->name }}
                    </span>
                </div>

                <h4 style="font-size:19px;margin:44px 0 var(--space-3)">{{ __('detail.description') }}</h4>
                @foreach (($job->description ?? []) as $paragraph)
                    <p style="font-size:15px;max-width:64ch;line-height:1.85">{{ $paragraph }}</p>
                @endforeach

                <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;margin-top:var(--space-8)">
                    @auth
                        @if (auth()->user()->isSeeker())
                            <button type="button" class="btn-blue" @click="apply = true">
                                <i class="ph ph-paper-plane-tilt"></i>{{ __('apply.cta') }}
                            </button>
                        @endif
                    @else
                        <a href="{{ lroute('login') }}" class="btn-blue">
                            <i class="ph ph-paper-plane-tilt"></i>{{ __('apply.cta') }}
                        </a>
                    @endauth

                    @if ($job->contactWhatsapp() || $job->contactPhone() || $job->contactEmail())
                        <button type="button" class="btn btn-secondary" @click="contact = true">
                            {{ __('detail.viewContact') }}
                        </button>
                    @endif
                </div>

                {{-- The rule belongs to the similar-jobs block, not to the page:
                     a listing whose category holds nothing else should end on
                     its own content, not on a divider with a gap under it. --}}
                @if ($similar->isNotEmpty())
                    <div class="hr" style="margin:44px 0"></div>

                    <h4 style="font-size:19px;margin-bottom:var(--space-4)">{{ __('detail.similar') }}</h4>
                    <div class="list-panel">
                        @foreach ($similar as $other)
                            @include('partials.job-row', ['job' => $other, 'variant' => 'compact'])
                        @endforeach
                    </div>
                @endif

            </main>

            <aside style="min-width:0;display:flex;flex-direction:column;gap:var(--space-6)">

                <div class="card elev-sm" style="padding:var(--space-6);gap:var(--space-3)">
                    <span class="card-kicker">{{ __('detail.postedBy') }}</span>

                    <div style="display:flex;align-items:center;gap:10px">
                        <span class="mono" style="width:38px;height:38px">@include('partials.company-icon')</span>
                        <span class="card-title" style="font-size:16px;line-height:1.4">{{ $job->company?->name }}@if ($job->company?->is_verified)@include('partials.verified-badge')@endif</span>
                    </div>

                    @if ($job->company?->blurb)
                        <p class="card-body" style="font-size:13px;line-height:1.7">{{ $job->company->blurb }}</p>
                    @endif

                    @if ($job->company)
                        <a href="{{ lroute('jobs.index', ['q' => $job->company->name_en]) }}"
                           class="btn btn-ghost" style="align-self:flex-start">
                            {{ __('detail.allEmployerAds') }}<i class="ph {{ $arrowIcon }}" style="margin-inline-start:5px"></i>
                        </a>
                    @endif
                </div>

                <form method="POST" action="{{ lroute('saved.store', $job) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-block">
                        <i class="ph ph-bookmark-simple"></i>{{ __('detail.save') }}
                    </button>
                </form>

                <div style="display:flex;gap:var(--space-6);font-size:12px">
                    <a href="#" style="display:flex;align-items:center;gap:5px">
                        <i class="ph ph-share-network"></i>{{ __('detail.share') }}
                    </a>
                    <button type="button" class="text-muted"
                            style="display:flex;align-items:center;gap:5px;border:0;background:none;font:inherit;font-size:12px;cursor:pointer"
                            @click="report = true">
                        <i class="ph ph-flag"></i>{{ __('detail.report') }}
                    </button>
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
    </div>

@endsection

@push('dialogs')
    {{-- ── Contact ──────────────────────────────────────────────────────────
         Each row is the whole hit area, so the number is tappable rather than
         something to copy by hand. The values are forced to LTR: a phone number
         or an email address reads left-to-right even on the Arabic page. --}}
    <div class="dialog-backdrop" x-show="contact" x-cloak @click="contact = false">
        <div class="dialog" dir="{{ Locale::dir() }}" style="padding:var(--space-8)" @click.stop>
            <div class="dialog-title">{{ __('contactDialog.title') }}</div>

            <div style="margin-top:var(--space-3)">
                @if ($job->contactWhatsapp())
                    <a class="contact-row" href="https://wa.me/{{ Phone::forWhatsapp($job->contactWhatsapp()) }}"
                       target="_blank" rel="noopener">
                        <span class="contact-ico"><i class="ph ph-whatsapp-logo"></i></span>
                        <span style="min-width:0">
                            <span class="contact-k">{{ __('contactDialog.whatsapp') }}</span>
                            <span class="contact-v">{{ Phone::format($job->contactWhatsapp()) }}</span>
                        </span>
                    </a>
                @endif

                @if ($job->contactPhone())
                    <a class="contact-row" href="tel:{{ preg_replace('/[^0-9+]/', '', $job->contactPhone()) }}">
                        <span class="contact-ico"><i class="ph ph-phone"></i></span>
                        <span style="min-width:0">
                            <span class="contact-k">{{ __('contactDialog.phone') }}</span>
                            <span class="contact-v">{{ Phone::format($job->contactPhone()) }}</span>
                        </span>
                    </a>
                @endif

                @if ($job->contactEmail())
                    <a class="contact-row" href="mailto:{{ $job->contactEmail() }}">
                        <span class="contact-ico"><i class="ph ph-envelope-simple"></i></span>
                        <span style="min-width:0">
                            <span class="contact-k">{{ __('contactDialog.email') }}</span>
                            <span class="contact-v">{{ $job->contactEmail() }}</span>
                        </span>
                    </a>
                @endif
            </div>

            <div class="dialog-actions">
                <button type="button" class="btn btn-secondary" @click="contact = false">
                    {{ __('contactDialog.close') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ── Apply ────────────────────────────────────────────────────────── --}}
    @auth
        @if (auth()->user()->isSeeker())
            <div class="dialog-backdrop" x-show="apply" x-cloak @click="apply = false">
                <form class="dialog" dir="{{ Locale::dir() }}" style="padding:var(--space-8)" @click.stop
                      method="POST" action="{{ lroute('jobs.apply', $job) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="dialog-title">{{ __('apply.title') }}</div>
                    <p class="dialog-body" style="margin:0">{{ __('apply.intro') }}</p>

                    <div class="field">
                        <label for="apply-cv">{{ __('apply.cv') }}</label>
                        <input id="apply-cv" class="input" type="file" name="cv" accept=".pdf,.doc,.docx">
                        <span class="text-muted" style="font-size:12px">{{ __('apply.cvHint') }}</span>
                    </div>

                    <div class="field">
                        <label for="apply-letter">{{ __('apply.coverLetter') }}</label>
                        <textarea id="apply-letter" class="input" name="cover_letter" rows="4"></textarea>
                    </div>

                    <div class="dialog-actions">
                        <button type="button" class="btn btn-secondary" @click="apply = false">
                            {{ __('contactDialog.close') }}
                        </button>
                        <button type="submit" class="btn-blue">{{ __('apply.send') }}</button>
                    </div>
                </form>
            </div>
        @endif
    @endauth

    {{-- ── Report ─────────────────────────────────────────────────────────── --}}
    <div class="dialog-backdrop" x-show="report" x-cloak @click="report = false">
        <form class="dialog" dir="{{ Locale::dir() }}" style="padding:var(--space-8)" @click.stop
              method="POST" action="{{ lroute('jobs.report', $job) }}">
            @csrf
            <div class="dialog-title">{{ __('report.title') }}</div>

            <div class="field">
                <label for="report-reason">{{ __('report.reason') }}</label>
                <select id="report-reason" class="input" name="reason" required>
                    @foreach (\App\Models\JobReport::REASONS as $reason)
                        <option value="{{ $reason }}">{{ __('report.'.$reason) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="report-note">{{ __('report.note') }}</label>
                <textarea id="report-note" class="input" name="note" rows="3" maxlength="2000"></textarea>
            </div>

            <div class="dialog-actions">
                <button type="button" class="btn btn-secondary" @click="report = false">
                    {{ __('contactDialog.close') }}
                </button>
                <button type="submit" class="btn-blue">{{ __('report.submit') }}</button>
            </div>
        </form>
    </div>
@endpush
