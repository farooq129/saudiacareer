@extends('layouts.portal')

@section('title', __('employer.company.title'))
@section('heading', __('employer.company.title'))

@section('content')

    <form method="POST" action="{{ lroute('employer.company.update') }}" class="adm-detail" style="align-items:start">
        @csrf @method('PUT')

        <div>
            <div class="adm-block">
                <h5>{{ __('employer.company.title') }}</h5>

                <p class="text-muted" style="font-size:13px;margin-bottom:var(--space-6)">
                    {{ __('employer.company.intro') }}
                </p>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="name_ar">{{ __('employer.company.nameAr') }} *</label>
                        <input id="name_ar" class="input" name="name_ar" dir="rtl" required
                               value="{{ old('name_ar', $company->name_ar) }}">
                        @error('name_ar') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="name_en">{{ __('employer.company.nameEn') }} *</label>
                        <input id="name_en" class="input" name="name_en" dir="ltr" required
                               value="{{ old('name_en', $company->name_en) }}">
                        @error('name_en') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="blurb_ar">{{ __('employer.company.blurbAr') }}</label>
                        <textarea id="blurb_ar" class="input" name="blurb_ar" dir="rtl" rows="4">{{ old('blurb_ar', $company->blurb_ar) }}</textarea>
                    </div>

                    <div class="field">
                        <label for="blurb_en">{{ __('employer.company.blurbEn') }}</label>
                        <textarea id="blurb_en" class="input" name="blurb_en" dir="ltr" rows="4">{{ old('blurb_en', $company->blurb_en) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.listing.overview') }}</h5>

                <div class="adm-facts">
                    <div class="field">
                        <label for="cr_number">{{ __('employer.company.crNumber') }}</label>
                        <input id="cr_number" class="input" name="cr_number" dir="ltr"
                               value="{{ old('cr_number', $company->cr_number) }}">
                        <span class="text-muted" style="font-size:12px">{{ __('employer.company.crHint') }}</span>
                    </div>

                    <div class="field">
                        <label for="city_id">{{ __('search.city') }}</label>
                        <select id="city_id" class="input" name="city_id">
                            <option value="">—</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id', $company->city_id) == $city->id)>
                                    {{ $city->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="staff_count">{{ __('employer.company.staffCount') }}</label>
                        <input id="staff_count" class="input" name="staff_count" type="number" min="1" dir="ltr"
                               value="{{ old('staff_count', $company->staff_count) }}">
                    </div>

                    <div class="field">
                        <label for="member_since">{{ __('employer.company.memberSince') }}</label>
                        <input id="member_since" class="input" name="member_since" type="number"
                               min="1950" max="{{ date('Y') }}" dir="ltr"
                               value="{{ old('member_since', $company->member_since) }}">
                    </div>

                    <div class="field">
                        <label for="website">{{ __('employer.company.website') }}</label>
                        <input id="website" class="input" name="website" type="url" dir="ltr"
                               placeholder="https://" value="{{ old('website', $company->website) }}">
                        @error('website') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="adm-block">
                <h5>{{ __('admin.listing.contact') }}</h5>

                <div class="adm-facts">
                    <div class="field">
                        <label for="whatsapp">{{ __('contactDialog.whatsapp') }}</label>
                        <input id="whatsapp" class="input" name="whatsapp" dir="ltr" inputmode="tel"
                               value="{{ old('whatsapp', $company->whatsapp) }}">
                    </div>

                    <div class="field">
                        <label for="phone">{{ __('contactDialog.phone') }}</label>
                        <input id="phone" class="input" name="phone" dir="ltr" inputmode="tel"
                               value="{{ old('phone', $company->phone) }}">
                    </div>

                    <div class="field">
                        <label for="email">{{ __('contactDialog.email') }}</label>
                        <input id="email" class="input" name="email" type="email" dir="ltr"
                               value="{{ old('email', $company->email) }}">
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>

        <aside>
            <div class="adm-block">
                <h5>{{ __('employer.company.verified') }}</h5>

                @if ($company->exists && $company->is_verified)
                    <span class="pill pill-published">
                        <i class="ph ph-seal-check"></i>{{ __('employer.company.verified') }}
                    </span>
                @else
                    <span class="pill pill-pending">
                        <i class="ph ph-hourglass"></i>{{ __('employer.company.notVerified') }}
                    </span>
                @endif

                <p class="text-muted" style="font-size:12.5px;margin:var(--space-4) 0 0">
                    {{ __('employer.company.verifiedHint') }}
                </p>
            </div>

            <div class="adm-block">
                <button type="submit" class="btn-blue btn-wide">{{ __('employer.company.save') }}</button>
            </div>
        </aside>
    </form>

@endsection
