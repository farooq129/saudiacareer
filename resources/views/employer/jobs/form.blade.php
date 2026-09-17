@extends('layouts.portal')

@php
    use App\Models\Job;

    $editing = $job->exists;

    /*
     * Admin and moderator reuse this form to correct a listing, so the target
     * and the submit block are overridable. Everything between them — the
     * bilingual fields, the placement selects — is identical whoever is typing,
     * and duplicating it into a second view is how the two drift apart.
     */
    $adminMode = $adminMode ?? false;
    $action = $action ?? ($editing ? lroute('employer.jobs.update', $job) : lroute('employer.jobs.store'));

    /*
     * The list fields are stored as JSON arrays and edited as one-per-line
     * textareas, so they are flattened back on the way in. old() wins after a
     * failed validation pass, which is why it is checked first.
     */
    $lines = function (string $key) use ($job) {
        if (old($key) !== null) {
            return old($key);
        }

        $value = $job->{$key};

        return is_array($value) ? implode("\n", $value) : (string) $value;
    };
@endphp

@section('title', $formTitle ?? ($editing ? __('employer.form.editTitle') : __('employer.form.newTitle')))
@section('heading', $formTitle ?? ($editing ? __('employer.form.editTitle') : __('employer.form.newTitle')))

@section('content')

    @if ($editing && $job->status === Job::STATUS_REJECTED && $job->rejection_reason)
        <div class="flash flash-error" style="margin-top:0">
            <strong>{{ __('employer.jobs.rejectedNotice') }}</strong>
            <div style="margin-top:4px">{{ $job->rejection_reason }}</div>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="adm-detail" style="align-items:start">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div>
            {{-- ── Basics ───────────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('employer.form.basics') }}</h5>

                <p class="text-muted" style="font-size:13px;margin-bottom:var(--space-6)">
                    {{ __('employer.form.bothLanguages') }}
                </p>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="title_ar">{{ __('employer.form.titleAr') }} *</label>
                        <input id="title_ar" class="input" name="title_ar" dir="rtl" required
                               value="{{ old('title_ar', $job->title_ar) }}">
                        @error('title_ar') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="title_en">{{ __('employer.form.titleEn') }} *</label>
                        <input id="title_en" class="input" name="title_en" dir="ltr" required
                               value="{{ old('title_en', $job->title_en) }}">
                        @error('title_en') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="excerpt_ar">{{ __('employer.form.excerptAr') }}</label>
                        <textarea id="excerpt_ar" class="input" name="excerpt_ar" dir="rtl" rows="2">{{ old('excerpt_ar', $job->excerpt_ar) }}</textarea>
                    </div>

                    <div class="field">
                        <label for="excerpt_en">{{ __('employer.form.excerptEn') }}</label>
                        <textarea id="excerpt_en" class="input" name="excerpt_en" dir="ltr" rows="2">{{ old('excerpt_en', $job->excerpt_en) }}</textarea>
                    </div>
                </div>

                <p class="text-muted" style="font-size:12px;margin:0">{{ __('employer.form.excerptHint') }}</p>
            </div>

            {{-- ── Details ──────────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('employer.form.details') }}</h5>

                @foreach (['description'] as $base)
                    <div class="adm-bilingual">
                        @foreach (['ar', 'en'] as $lang)
                            <div class="field">
                                <label for="{{ $base }}_{{ $lang }}">
                                    {{ __('employer.form.'.$base.ucfirst($lang)) }}
                                </label>
                                <textarea id="{{ $base }}_{{ $lang }}" class="input"
                                          name="{{ $base }}_{{ $lang }}" dir="{{ $lang === 'ar' ? 'rtl' : 'ltr' }}"
                                          rows="{{ $base === 'description' ? 5 : 4 }}">{{ $lines($base.'_'.$lang) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <p class="text-muted" style="font-size:12px;margin:0">{{ __('employer.form.linesHint') }}</p>
            </div>

            {{-- ── Pay and terms ────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('employer.form.pay') }}</h5>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="salary_min">{{ __('employer.form.salaryMin') }}</label>
                        <input id="salary_min" class="input" name="salary_min" type="number" min="0" dir="ltr"
                               value="{{ old('salary_min', $job->salary_min) }}">
                        @error('salary_min') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="salary_max">{{ __('employer.form.salaryMax') }}</label>
                        <input id="salary_max" class="input" name="salary_max" type="number" min="0" dir="ltr"
                               value="{{ old('salary_max', $job->salary_max) }}">
                        @error('salary_max') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <p class="text-muted" style="font-size:12px;margin:0 0 var(--space-6)">
                    {{ __('employer.form.salaryHint') }}
                </p>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="salary_note_ar">{{ __('employer.form.salaryNoteAr') }}</label>
                        <input id="salary_note_ar" class="input" name="salary_note_ar" dir="rtl"
                               value="{{ old('salary_note_ar', $job->salary_note_ar) }}">
                    </div>
                    <div class="field">
                        <label for="salary_note_en">{{ __('employer.form.salaryNoteEn') }}</label>
                        <input id="salary_note_en" class="input" name="salary_note_en" dir="ltr"
                               value="{{ old('salary_note_en', $job->salary_note_en) }}">
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="experience_ar">{{ __('employer.form.experienceAr') }}</label>
                        <input id="experience_ar" class="input" name="experience_ar" dir="rtl"
                               value="{{ old('experience_ar', $job->experience_ar) }}">
                    </div>
                    <div class="field">
                        <label for="experience_en">{{ __('employer.form.experienceEn') }}</label>
                        <input id="experience_en" class="input" name="experience_en" dir="ltr"
                               value="{{ old('experience_en', $job->experience_en) }}">
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="eligibility_ar">{{ __('employer.form.eligibilityAr') }}</label>
                        <input id="eligibility_ar" class="input" name="eligibility_ar" dir="rtl"
                               value="{{ old('eligibility_ar', $job->eligibility_ar) }}">
                    </div>
                    <div class="field">
                        <label for="eligibility_en">{{ __('employer.form.eligibilityEn') }}</label>
                        <input id="eligibility_en" class="input" name="eligibility_en" dir="ltr"
                               value="{{ old('eligibility_en', $job->eligibility_en) }}">
                    </div>
                </div>

                <label class="radio" style="margin-top:var(--space-3)">
                    <input type="checkbox" name="transfer_available" value="1"
                           @checked(old('transfer_available', $job->transfer_available))
                           style="position:static;width:auto;height:auto;opacity:1">
                    {{ __('employer.form.transfer') }}
                </label>
            </div>

            {{-- ── Contact ──────────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('employer.form.contact') }}</h5>

                <p class="text-muted" style="font-size:12px;margin-bottom:var(--space-4)">
                    {{ __('employer.form.contactHint') }}
                </p>

                <div class="adm-facts">
                    <div class="field">
                        <label for="whatsapp">{{ __('contactDialog.whatsapp') }}</label>
                        <input id="whatsapp" class="input" name="whatsapp" dir="ltr" inputmode="tel"
                               value="{{ old('whatsapp', $job->whatsapp) }}">
                    </div>
                    <div class="field">
                        <label for="phone">{{ __('contactDialog.phone') }}</label>
                        <input id="phone" class="input" name="phone" dir="ltr" inputmode="tel"
                               value="{{ old('phone', $job->phone) }}">
                    </div>
                    <div class="field">
                        <label for="email">{{ __('contactDialog.email') }}</label>
                        <input id="email" class="input" name="email" type="email" dir="ltr"
                               value="{{ old('email', $job->email) }}">
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Placement and submit ─────────────────────────────────────── --}}
        <aside>
            <div class="adm-block">
                <h5>{{ __('search.filter') ?? __('admin.listing.overview') }}</h5>

                <div class="field">
                    <label for="city_id">{{ __('search.city') }} *</label>
                    <select id="city_id" class="input" name="city_id" required>
                        <option value="">—</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected(old('city_id', $job->city_id) == $city->id)>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('city_id') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="category_id">{{ __('search.category') }} *</label>
                    <select id="category_id" class="input" name="category_id" required>
                        <option value="">—</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $job->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field" style="margin-top:var(--space-4)">
                    <label for="employment_type_id">{{ __('search.employmentType') }} *</label>
                    <select id="employment_type_id" class="input" name="employment_type_id" required>
                        <option value="">—</option>
                        @foreach ($employmentTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('employment_type_id', $job->employment_type_id) == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employment_type_id') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="adm-block">
                <div class="adm-actions">
                    @if ($adminMode)
                        {{-- No draft/submit pair here: a staff edit corrects the
                             content and leaves the listing in whatever state it
                             was already in. Approving, rejecting and
                             withdrawing stay on the review screen, where the
                             reason box and the audit trail live. --}}
                        <button type="submit" class="btn-blue btn-wide">
                            {{ __('admin.edit.save') }}
                        </button>

                        <a href="{{ lroute('admin.jobs.show', $job) }}" class="btn btn-secondary btn-block">
                            {{ __('admin.edit.cancel') }}
                        </a>

                        <p class="text-muted" style="font-size:12px;margin:var(--space-3) 0 0">
                            {{ __('admin.edit.staffNote') }}
                        </p>
                    @else
                        <button type="submit" name="intent" value="submit" class="btn-blue btn-wide">
                            @if ($editing)
                                {{ __('employer.form.update') }}
                            @elseif (config('board.listing.moderated'))
                                {{ __('employer.form.submit') }}
                            @else
                                {{ __('employer.form.submitDirect') }}
                            @endif
                        </button>

                        <button type="submit" name="intent" value="draft" class="btn btn-secondary btn-block">
                            {{ __('employer.form.saveDraft') }}
                        </button>

                        @if (config('board.listing.moderated'))
                            <p class="text-muted" style="font-size:12px;margin:var(--space-3) 0 0">
                                {{ __('employer.form.moderationNote') }}
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </aside>
    </form>

@endsection
