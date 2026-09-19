@extends('layouts.portal')

@php
    use App\Models\JobSeekerPost;

    $editing = $post->exists;

    // Admin and moderator reuse this form to correct a post; see the note in
    // employer/jobs/form.blade.php for why it is shared rather than copied.
    $adminMode = $adminMode ?? false;

    /*
     * Expected pay is hidden for now, on the seeker's form and on the staff
     * edit screen alike. The columns stay on job_seeker_posts and the rules
     * stay in PostRequest, so bringing it back is this one flag — nothing to
     * migrate, and any figures already stored are left untouched rather than
     * blanked, because a form that does not post a key cannot overwrite it.
     */
    $showExpectedSalary = false;

    // Employment type is shelved on seeker posts on the same terms: the column
    // and the relation stay, only the control goes. The public post hides it
    // to match, so a seeded value cannot show a field nobody can edit.
    $showEmploymentType = false;
    $action = $action ?? ($editing ? lroute('seeker.posts.update', $post) : lroute('seeker.posts.store'));
@endphp

@section('title', $formTitle ?? ($editing ? __('seeker.form.editTitle') : __('seeker.form.newTitle')))
@section('heading', $formTitle ?? ($editing ? __('seeker.form.editTitle') : __('seeker.form.newTitle')))

@section('content')

    @if ($editing && $post->status === JobSeekerPost::STATUS_REJECTED && $post->rejection_reason)
        <div class="flash flash-error" style="margin-top:0">
            <strong>{{ __('seeker.posts.rejectedNotice') }}</strong>
            <div style="margin-top:4px">{{ $post->rejection_reason }}</div>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data"
          class="adm-detail" style="align-items:start">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div>
            <div class="adm-block">
                <h5>{{ __('employer.form.basics') }}</h5>

                <p class="text-muted" style="font-size:13px;margin-bottom:var(--space-4)">
                    {{ __('seeker.form.intro') }}
                </p>
                <p class="text-muted" style="font-size:13px;margin-bottom:var(--space-6)">
                    {{ __('seeker.form.bothLanguages') }}
                </p>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="display_name_ar">{{ __('seeker.form.nameAr') }} *</label>
                        <input id="display_name_ar" class="input" name="display_name_ar" dir="rtl" required
                               value="{{ old('display_name_ar', $post->display_name_ar) }}">
                        @error('display_name_ar') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="display_name_en">{{ __('seeker.form.nameEn') }} *</label>
                        <input id="display_name_en" class="input" name="display_name_en" dir="ltr" required
                               value="{{ old('display_name_en', $post->display_name_en) }}">
                        @error('display_name_en') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="headline_ar">{{ __('seeker.form.headlineAr') }} *</label>
                        <input id="headline_ar" class="input" name="headline_ar" dir="rtl" required
                               value="{{ old('headline_ar', $post->headline_ar) }}">
                        @error('headline_ar') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="headline_en">{{ __('seeker.form.headlineEn') }} *</label>
                        <input id="headline_en" class="input" name="headline_en" dir="ltr" required
                               value="{{ old('headline_en', $post->headline_en) }}">
                        @error('headline_en') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="pitch_ar">{{ __('seeker.form.pitchAr') }}</label>
                        <textarea id="pitch_ar" class="input" name="pitch_ar" dir="rtl" rows="4">{{ old('pitch_ar', $post->pitch_ar) }}</textarea>
                    </div>

                    <div class="field">
                        <label for="pitch_en">{{ __('seeker.form.pitchEn') }}</label>
                        <textarea id="pitch_en" class="input" name="pitch_en" dir="ltr" rows="4">{{ old('pitch_en', $post->pitch_en) }}</textarea>
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="experience_ar">{{ __('seeker.form.experienceAr') }}</label>
                        <input id="experience_ar" class="input" name="experience_ar" dir="rtl"
                               value="{{ old('experience_ar', $post->experience_ar) }}">
                    </div>

                    <div class="field">
                        <label for="experience_en">{{ __('seeker.form.experienceEn') }}</label>
                        <input id="experience_en" class="input" name="experience_en" dir="ltr"
                               value="{{ old('experience_en', $post->experience_en) }}">
                    </div>
                </div>

                <p class="text-muted" style="font-size:12px;margin:0">{{ __('seeker.form.experienceHint') }}</p>
            </div>

            <div class="adm-block">
                @if ($showExpectedSalary)
                    <h5>{{ __('seeker.form.expectedSalary') }}</h5>

                    <div class="adm-bilingual">
                        <div class="field">
                            <label for="expected_salary_min">{{ __('seeker.form.salaryMin') }}</label>
                            <input id="expected_salary_min" class="input" name="expected_salary_min"
                                   type="number" min="0" dir="ltr"
                                   value="{{ old('expected_salary_min', $post->expected_salary_min) }}">
                        </div>

                        <div class="field">
                            <label for="expected_salary_max">{{ __('seeker.form.salaryMax') }}</label>
                            <input id="expected_salary_max" class="input" name="expected_salary_max"
                                   type="number" min="0" dir="ltr"
                                   value="{{ old('expected_salary_max', $post->expected_salary_max) }}">
                            @error('expected_salary_max') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <p class="text-muted" style="font-size:12px;margin:0 0 var(--space-6)">
                        {{ __('seeker.form.salaryHint') }}
                    </p>
                @else
                    {{-- The two checkboxes below carried the pay heading; with
                         pay hidden they need one of their own. --}}
                    <h5>{{ __('seeker.form.availability') }}</h5>
                @endif

                <label class="radio" style="display:flex;margin-bottom:var(--space-3)">
                    <input type="checkbox" name="transfer_available" value="1"
                           @checked(old('transfer_available', $post->transfer_available))
                           style="position:static;width:auto;height:auto;opacity:1">
                    {{ __('seeker.form.transfer') }}
                </label>

                <label class="radio" style="display:flex">
                    <input type="checkbox" name="available_immediately" value="1"
                           @checked(old('available_immediately', $post->available_immediately))
                           style="position:static;width:auto;height:auto;opacity:1">
                    {{ __('seeker.form.available') }}
                </label>
            </div>

            <div class="adm-block">
                <h5>{{ __('seeker.form.cv') }}</h5>

                <div class="field">
                    <input class="input" type="file" name="cv" accept=".pdf,.doc,.docx">
                    <span class="text-muted" style="font-size:12px">{{ __('seeker.form.cvHint') }}</span>
                    @error('cv') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                @if ($post->cv_path)
                    <p class="text-muted" style="font-size:12.5px;margin:var(--space-3) 0 0">
                        {{ __('seeker.form.cvCurrent') }}
                    </p>
                @endif
            </div>

            <div class="adm-block">
                <h5>{{ __('seeker.form.contact') }}</h5>

                <div class="adm-facts">
                    <div class="field">
                        <label for="whatsapp">{{ __('contactDialog.whatsapp') }}</label>
                        <input id="whatsapp" class="input" name="whatsapp" dir="ltr" inputmode="tel"
                               value="{{ old('whatsapp', $post->whatsapp) }}">
                    </div>

                    <div class="field">
                        <label for="phone">{{ __('contactDialog.phone') }}</label>
                        <input id="phone" class="input" name="phone" dir="ltr" inputmode="tel"
                               value="{{ old('phone', $post->phone) }}">
                    </div>

                    <div class="field">
                        <label for="email">{{ __('contactDialog.email') }}</label>
                        <input id="email" class="input" name="email" type="email" dir="ltr"
                               value="{{ old('email', $post->email) }}">
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        <aside>
            <div class="adm-block">
                <h5>{{ __('admin.listing.overview') }}</h5>

                <div class="field">
                    <label for="city_id">{{ __('search.city') }} *</label>
                    <select id="city_id" class="input" name="city_id" required>
                        <option value="">—</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected(old('city_id', $post->city_id) == $city->id)>
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
                            <option value="{{ $category->id }}" @selected(old('category_id', $post->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                @if ($showEmploymentType)
                    <div class="field" style="margin-top:var(--space-4)">
                        <label for="employment_type_id">{{ __('search.employmentType') }}</label>
                        <select id="employment_type_id" class="input" name="employment_type_id">
                            <option value="">{{ __('search.anyType') }}</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('employment_type_id', $post->employment_type_id) == $type->id)>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="adm-block">
                <div class="adm-actions">
                    @if ($adminMode)
                        <button type="submit" class="btn-blue btn-wide">
                            {{ __('admin.edit.save') }}
                        </button>

                        <a href="{{ lroute('admin.seekers.show', $post) }}" class="btn btn-secondary btn-block">
                            {{ __('admin.edit.cancel') }}
                        </a>

                        <p class="text-muted" style="font-size:12px;margin:var(--space-3) 0 0">
                            {{ __('admin.edit.staffNote') }}
                        </p>
                    @else
                        <button type="submit" name="intent" value="submit" class="btn-blue btn-wide">
                            @if ($editing)
                                {{ __('seeker.form.update') }}
                            @elseif (config('board.listing.moderated'))
                                {{ __('seeker.form.submit') }}
                            @else
                                {{ __('seeker.form.submitDirect') }}
                            @endif
                        </button>

                        <button type="submit" name="intent" value="draft" class="btn btn-secondary btn-block">
                            {{ __('seeker.form.saveDraft') }}
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
