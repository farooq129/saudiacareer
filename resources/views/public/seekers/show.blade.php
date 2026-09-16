@extends('layouts.app')

@php
    use App\Support\Locale;
    use App\Support\Phone;
    use App\Support\Plural;

    $isAr = Locale::isRtl();
    $crumbIcon = $isAr ? 'ph-caret-left' : 'ph-caret-right';
    $postedMinutes = (int) ($post->published_at?->diffInMinutes(now()) ?? 0);
@endphp

@section('title', $post->headline.' — '.$post->display_name)
{{-- @see public/show.blade.php — a null second argument leaks a buffer. --}}
@section('description', $post->pitch ?: $post->headline)

@section('content')

    <div class="page-detail-wrap">

        <nav style="display:flex;gap:var(--space-2);align-items:center;font-size:12px;margin-bottom:var(--space-8)">
            <a href="{{ lroute('home') }}">{{ __('detail.home') }}</a>
            <i class="ph {{ $crumbIcon }}" style="color:var(--color-neutral-600);font-size:11px"></i>
            <a href="{{ lroute('seekers.index') }}">{{ __('home.seekerPosts') }}</a>
            <i class="ph {{ $crumbIcon }}" style="color:var(--color-neutral-600);font-size:11px"></i>
            <span class="text-muted">{{ $post->city->name }}</span>
        </nav>

        <div class="page-detail">
            <main style="min-width:0">

                <h1 style="font-size:clamp(25px,3.4vw,37px);margin-bottom:var(--space-4);line-height:1.3">
                    {{ $post->headline }}
                </h1>

                <div style="display:flex;align-items:center;gap:var(--space-4);flex-wrap:wrap;font-size:14px">
                    <span style="display:flex;align-items:center;gap:9px">
                        {{-- A person gets a round monogram, an employer a square
                             one — the only thing that tells the two feeds apart
                             at a glance on a mixed page. --}}
                        <span class="mono mono-sm" style="border-radius:50%;background:var(--color-surface);box-shadow:var(--shadow-sm)">
                            {{ $post->monogram() }}
                        </span>
                        {{ $post->display_name }}
                    </span>

                    @if ($post->transfer_available)
                        <span class="tag tag-neutral">{{ __('tag.transfer') }}</span>
                    @endif

                    <span class="text-muted" style="display:flex;align-items:center;gap:6px">
                        <i class="ph ph-map-pin"></i>{{ $post->city->name }}
                    </span>

                    <span class="text-muted" style="display:flex;align-items:center;gap:6px">
                        <i class="ph ph-clock"></i>{{ Plural::ago($postedMinutes) }}
                    </span>
                </div>

                <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:var(--space-6);margin:var(--space-8) 0 0;padding:var(--space-6);border-radius:var(--radius-sm);background:var(--color-surface);box-shadow:var(--shadow-sm)">
                    <div>
                        <dt class="card-kicker">{{ __('search.category') }}</dt>
                        <dd style="margin:0;font-size:15px">{{ $post->category->name }}</dd>
                    </div>

                    @if ($post->experience)
                        <div>
                            <dt class="card-kicker">{{ __('detail.experienceLabel') }}</dt>
                            <dd style="margin:0;font-size:15px">{{ $post->experience }}</dd>
                        </div>
                    @endif

                    @if ($post->employmentType)
                        <div>
                            <dt class="card-kicker">{{ __('search.employmentType') }}</dt>
                            <dd style="margin:0;font-size:15px">{{ $post->employmentType->name }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($post->pitch)
                    <h4 style="font-size:19px;margin:44px 0 var(--space-3)">{{ __('detail.description') }}</h4>
                    <p style="font-size:15px;max-width:64ch;line-height:1.85">{{ $post->pitch }}</p>
                @endif

                {{-- The same shape as a listing's: the details sit behind a
                     click rather than on the page, so a scraper pulling the
                     HTML does not walk away with every seeker's mobile number. --}}
                @if ($post->hasContact())
                    <button type="button" class="btn-blue" style="margin-top:var(--space-8)" @click="contact = true">
                        <i class="ph ph-address-book"></i>{{ __('detail.viewContact') }}
                    </button>
                @endif

            </main>

            <aside style="min-width:0;display:flex;flex-direction:column;gap:var(--space-6)">
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
    {{-- Same component as a listing's contact dialog: each row is the whole hit
         area so the number is tappable rather than something to copy by hand,
         and the values are forced to LTR because a phone number or an email
         address reads left-to-right even on the Arabic page. --}}
    <div class="dialog-backdrop" x-show="contact" x-cloak @click="contact = false">
        <div class="dialog" dir="{{ Locale::dir() }}" style="padding:var(--space-8)" @click.stop>
            <div class="dialog-title">{{ __('contactDialog.title') }}</div>

            <p class="dialog-body" style="margin:0">
                {{ __('contactDialog.seekerNote', ['name' => $post->display_name]) }}
            </p>

            <div style="margin-top:var(--space-3)">
                @if ($post->contactWhatsapp())
                    <a class="contact-row" href="https://wa.me/{{ Phone::forWhatsapp($post->contactWhatsapp()) }}"
                       target="_blank" rel="noopener">
                        <span class="contact-ico"><i class="ph ph-whatsapp-logo"></i></span>
                        <span style="min-width:0">
                            <span class="contact-k">{{ __('contactDialog.whatsapp') }}</span>
                            <span class="contact-v">{{ Phone::format($post->contactWhatsapp()) }}</span>
                        </span>
                    </a>
                @endif

                @if ($post->contactPhone())
                    <a class="contact-row" href="tel:{{ preg_replace('/[^0-9+]/', '', $post->contactPhone()) }}">
                        <span class="contact-ico"><i class="ph ph-phone"></i></span>
                        <span style="min-width:0">
                            <span class="contact-k">{{ __('contactDialog.phone') }}</span>
                            <span class="contact-v">{{ Phone::format($post->contactPhone()) }}</span>
                        </span>
                    </a>
                @endif

                @if ($post->contactEmail())
                    <a class="contact-row" href="mailto:{{ $post->contactEmail() }}">
                        <span class="contact-ico"><i class="ph ph-envelope-simple"></i></span>
                        <span style="min-width:0">
                            <span class="contact-k">{{ __('contactDialog.email') }}</span>
                            <span class="contact-v">{{ $post->contactEmail() }}</span>
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
@endpush
