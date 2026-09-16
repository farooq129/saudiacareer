@extends('layouts.portal')

@php
    use App\Models\Job;
    use App\Support\Phone;
@endphp

@section('title', $job->title)
@section('heading', $job->title)

@section('content')

    <div class="adm-detail">

        <div>
            {{-- ── Overview ─────────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('admin.listing.overview') }}</h5>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('admin.status.all') }}</dt>
                        <dd>@include('admin.partials.status-pill', ['status' => $job->status])</dd>
                    </div>
                    <div>
                        <dt>{{ __('search.city') }}</dt>
                        <dd>{{ $job->city->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('search.category') }}</dt>
                        <dd>{{ $job->category->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('search.employmentType') }}</dt>
                        <dd>{{ $job->employmentType->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('detail.salaryLabel') }}</dt>
                        <dd>
                            @if ($job->salary_min)
                                @if ($job->salary_max)
                                    {{ __('units.salaryRange', ['min' => sar($job->salary_min), 'max' => sar($job->salary_max)]) }}
                                @else
                                    {{ __('units.salaryOne', ['n' => sar($job->salary_min)]) }}
                                @endif
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>{{ __('tag.transfer') }}</dt>
                        <dd>{{ $job->transfer_available ? '✓' : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.listing.submitted') }}</dt>
                        <dd>{{ $job->created_at->isoFormat('D MMM YYYY, HH:mm') }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.listing.published') }}</dt>
                        <dd>{{ $job->published_at?->isoFormat('D MMM YYYY') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.listing.expires') }}</dt>
                        <dd>{{ $job->expires_at?->isoFormat('D MMM YYYY') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- ── Content, both languages ──────────────────────────────────
                 Side by side rather than one at a time: half the point of the
                 review is noticing that one language is missing, or is the
                 other one run through a machine. --}}
            <div class="adm-block">
                <h5>{{ __('admin.listing.content') }}</h5>

                @foreach ([
                    'search.keyword' => 'title',
                    'admin.listing.description' => 'excerpt',
                ] as $label => $base)
                    <div class="adm-bilingual">
                        @foreach (['ar' => 'admin.listing.arabic', 'en' => 'admin.listing.english'] as $lang => $langLabel)
                            <div>
                                <span class="adm-lang-label">{{ __($langLabel) }}</span>
                                @if ($job->missingTranslation($base, $lang))
                                    <p class="adm-missing">{{ __('admin.listing.missing') }}</p>
                                @else
                                    <p style="margin:0">{{ $job->rawTranslation($base, $lang) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @foreach (['admin.listing.description' => 'description'] as $label => $base)
                    <h5 style="margin-top:var(--space-8)">{{ __($label) }}</h5>

                    <div class="adm-bilingual">
                        @foreach (['ar' => 'admin.listing.arabic', 'en' => 'admin.listing.english'] as $lang => $langLabel)
                            <div>
                                <span class="adm-lang-label">{{ __($langLabel) }}</span>
                                @php $items = $job->rawTranslation($base, $lang); @endphp

                                @if (empty($items))
                                    <p class="adm-missing">{{ __('admin.listing.missing') }}</p>
                                @else
                                    <ul style="margin:0;padding-inline-start:18px;font-size:14px;line-height:1.8">
                                        @foreach ($items as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- ── Reports against it ───────────────────────────────────── --}}
            @if ($job->reports->isNotEmpty())
                <div class="adm-block">
                    <h5>{{ __('admin.listing.reports') }}</h5>

                    <div class="adm-table-wrap">
                        <table class="adm-table">
                            <tbody>
                                @foreach ($job->reports as $report)
                                    <tr>
                                        <td>
                                            <span class="adm-cell-title">{{ __('report.'.$report->reason) }}</span>
                                            @if ($report->note)
                                                <span class="adm-cell-sub">{{ $report->note }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $report->user?->name ?? __('admin.reports.anonymous') }}</td>
                                        <td class="num">{{ $report->created_at->isoFormat('D MMM YYYY') }}</td>
                                        <td>
                                            @include('admin.partials.status-pill', [
                                                'status' => $report->status,
                                                'group' => 'admin.reports.statuses',
                                            ])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- ── Applications ─────────────────────────────────────────── --}}
            @if ($job->applications->isNotEmpty())
                <div class="adm-block">
                    <h5>{{ __('admin.listing.applications') }}</h5>

                    <div class="adm-table-wrap">
                        <table class="adm-table">
                            <tbody>
                                @foreach ($job->applications as $application)
                                    <tr>
                                        <td>
                                            <span class="adm-cell-title">{{ $application->user->name }}</span>
                                            <span class="adm-cell-sub" dir="ltr">{{ Phone::format($application->user->phone) }}</span>
                                        </td>
                                        <td>{{ $application->source }}</td>
                                        <td class="num">{{ $application->created_at->isoFormat('D MMM YYYY') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Actions rail ─────────────────────────────────────────────── --}}
        <aside>

            <div class="adm-block" x-data="{ rejecting: false, unpublishing: false }">
                <h5>{{ __('admin.listing.moderation') }}</h5>

                <div class="adm-actions">
                    @if ($job->status !== Job::STATUS_PUBLISHED)
                        <form method="POST" action="{{ lroute('admin.jobs.approve', $job) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-blue btn-wide">
                                <i class="ph ph-check"></i>{{ __('admin.queue.approve') }}
                            </button>
                        </form>
                    @endif

                    @if ($job->status !== Job::STATUS_REJECTED)
                        <button type="button" class="btn btn-secondary btn-block" @click="rejecting = ! rejecting">
                            <i class="ph ph-x"></i>{{ __('admin.queue.reject') }}
                        </button>

                        <form method="POST" action="{{ lroute('admin.jobs.reject', $job) }}"
                              x-show="rejecting" x-cloak style="margin-top:var(--space-2)">
                            @csrf @method('PATCH')
                            <div class="field">
                                <label for="reject-reason">{{ __('admin.listing.rejectPrompt') }}</label>
                                <textarea id="reject-reason" class="input" name="reason" rows="3" required minlength="5"></textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block">
                                {{ __('admin.queue.reject') }}
                            </button>
                        </form>
                    @endif

                    @if ($job->status === Job::STATUS_PUBLISHED)
                        <button type="button" class="btn btn-secondary btn-block" @click="unpublishing = ! unpublishing">
                            <i class="ph ph-eye-slash"></i>{{ __('admin.listing.unpublish') }}
                        </button>

                        <form method="POST" action="{{ lroute('admin.jobs.unpublish', $job) }}"
                              x-show="unpublishing" x-cloak style="margin-top:var(--space-2)">
                            @csrf @method('PATCH')
                            <div class="field">
                                <label for="unpublish-note">{{ __('admin.listing.unpublishNote') }}</label>
                                <textarea id="unpublish-note" class="input" name="reason" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-secondary btn-block">
                                {{ __('admin.listing.unpublish') }}
                            </button>
                        </form>
                    @endif

                    <div class="hr" style="margin:var(--space-3) 0"></div>

                    {{-- Promotion flags. Reversible presentation choices, kept
                         apart from the verdict above and never touching the
                         publish window. --}}
                    @foreach (['featured' => 'is_featured', 'urgent' => 'is_urgent'] as $flag => $column)
                        <form method="POST" action="{{ lroute('admin.jobs.flags', $job) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="flag" value="{{ $flag }}">
                            <button type="submit" class="btn btn-secondary btn-block">
                                <i class="ph {{ $job->{$column} ? 'ph-star-fill' : 'ph-star' }}"></i>
                                {{ __('admin.listing.toggle'.ucfirst($flag)) }}
                            </button>
                        </form>
                    @endforeach

                    <div class="hr" style="margin:var(--space-3) 0"></div>

                    <a href="{{ lroute('jobs.show', $job) }}" target="_blank" rel="noopener"
                       class="btn btn-secondary btn-block">
                        <i class="ph ph-arrow-square-out"></i>{{ __('admin.listing.viewPublic') }}
                    </a>

                    <form method="POST" action="{{ lroute('admin.jobs.destroy', $job) }}"
                          onsubmit="return confirm(@js(__('admin.listing.deleteConfirm')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary btn-block" style="color:var(--color-cta)">
                            <i class="ph ph-trash"></i>{{ __('admin.queue.delete') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- ── Review history ───────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('admin.listing.moderation') }}</h5>

                @if ($job->reviewed_at)
                    <p style="margin:0;font-size:13px">
                        {{ __('admin.listing.reviewedBy', [
                            'name' => $job->reviewer?->name ?? '—',
                            'date' => $job->reviewed_at->isoFormat('D MMM YYYY, HH:mm'),
                        ]) }}
                    </p>
                @else
                    <p class="text-muted" style="margin:0;font-size:13px">{{ __('admin.listing.neverReviewed') }}</p>
                @endif

                @if ($job->rejection_reason)
                    <div style="margin-top:var(--space-4)">
                        <span class="adm-lang-label">{{ __('admin.listing.rejectionReason') }}</span>
                        <p style="margin:0;font-size:13px">{{ $job->rejection_reason }}</p>
                    </div>
                @endif
            </div>

            {{-- ── Employer ─────────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('admin.listing.employer') }}</h5>

                @if ($job->company)
                    <p style="margin:0 0 var(--space-2)">
                        <a href="{{ lroute('admin.companies.show', $job->company) }}" class="adm-cell-title">
                            {{ $job->company->name }}
                        </a>
                        @if ($job->company->is_verified)
                            <i class="ph ph-seal-check" style="color:var(--color-accent-700)"></i>
                        @endif
                    </p>

                    @if ($job->company->cr_number)
                        <p class="text-muted" style="margin:0;font-size:12.5px">
                            {{ __('admin.companies.crNumber') }}: <span dir="ltr">{{ $job->company->cr_number }}</span>
                        </p>
                    @endif
                @endif

                <p class="text-muted" style="margin:var(--space-3) 0 0;font-size:12.5px">
                    {{ __('admin.listing.poster') }}: {{ $job->user->name }}
                    <span dir="ltr">({{ Phone::format($job->user->phone) }})</span>
                </p>
            </div>

            {{-- ── Contact and activity ─────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('admin.listing.contact') }}</h5>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('contactDialog.whatsapp') }}</dt>
                        <dd dir="ltr">{{ $job->contactWhatsapp() ? Phone::format($job->contactWhatsapp()) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('contactDialog.phone') }}</dt>
                        <dd dir="ltr">{{ $job->contactPhone() ? Phone::format($job->contactPhone()) : '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('contactDialog.email') }}</dt>
                        <dd dir="ltr" style="word-break:break-all">{{ $job->contactEmail() ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="hr" style="margin:var(--space-4) 0"></div>

                <dl class="adm-facts">
                    <div>
                        <dt>{{ __('admin.listing.views') }}</dt>
                        <dd class="num">{{ sar($job->views_count) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('admin.listing.applicationCount') }}</dt>
                        <dd class="num">{{ sar($job->applications_count) }}</dd>
                    </div>
                </dl>
            </div>

        </aside>
    </div>

@endsection
