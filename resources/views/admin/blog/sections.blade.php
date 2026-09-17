@extends('layouts.portal')

@section('title', __('admin.blog.sections.title'))
@section('heading', __('admin.blog.sections.title'))

@section('content')

    <div class="adm-head-actions">
        <a href="{{ lroute('admin.blog.index') }}" class="btn btn-secondary">
            {{ __('admin.blog.backToPosts') }}
        </a>
    </div>

    {{-- New section. Sits above the list because an empty board needs it
         first, and it is the only thing on this screen that creates a URL. --}}
    <form method="POST" action="{{ lroute('admin.blog.sections.store') }}" class="adm-panel"
          style="padding:var(--space-6)">
        @csrf

        <h5 style="margin-top:0">{{ __('admin.blog.sections.new') }}</h5>

        <div class="adm-bilingual">
            <div class="field">
                <label for="name_ar">{{ __('admin.blog.sections.nameAr') }} *</label>
                <input id="name_ar" class="input" name="name_ar" dir="rtl" required value="{{ old('name_ar') }}">
                @error('name_ar') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="name_en">{{ __('admin.blog.sections.nameEn') }} *</label>
                <input id="name_en" class="input" name="name_en" dir="ltr" required value="{{ old('name_en') }}">
                @error('name_en') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="adm-bilingual">
            <div class="field">
                <label for="blurb_ar">{{ __('admin.blog.sections.blurbAr') }}</label>
                <input id="blurb_ar" class="input" name="blurb_ar" dir="rtl" value="{{ old('blurb_ar') }}">
            </div>

            <div class="field">
                <label for="blurb_en">{{ __('admin.blog.sections.blurbEn') }}</label>
                <input id="blurb_en" class="input" name="blurb_en" dir="ltr" value="{{ old('blurb_en') }}">
            </div>
        </div>

        <div class="adm-filters">
            <div class="field field-grow">
                <label for="key">{{ __('admin.blog.sections.key') }} *</label>
                <input id="key" class="input" name="key" dir="ltr" required value="{{ old('key') }}">
                <span class="field-hint">{{ __('admin.blog.sections.keyHint') }}</span>
                @error('key') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label for="sort_order">{{ __('admin.blog.sections.order') }}</label>
                <input id="sort_order" class="input" type="number" name="sort_order" min="0"
                       value="{{ old('sort_order', 0) }}">
            </div>

            <label class="check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                <span>{{ __('admin.blog.sections.active') }}</span>
            </label>

            <button type="submit" class="btn-blue">{{ __('admin.blog.sections.add') }}</button>
        </div>
    </form>

    <section class="adm-panel">
        @error('section')
            <div class="flash flash-error" style="margin-top:0">{{ $message }}</div>
        @enderror

        @if ($sections->isEmpty())
            <div class="adm-empty">
                <i class="ph ph-folders"></i>
                <p style="margin:0">{{ __('admin.blog.sections.empty') }}</p>
            </div>
        @else
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.blog.sections.nameAr') }}</th>
                            <th>{{ __('admin.blog.sections.nameEn') }}</th>
                            <th>{{ __('admin.blog.sections.key') }}</th>
                            <th>{{ __('admin.blog.sections.order') }}</th>
                            <th>{{ __('admin.blog.sections.active') }}</th>
                            <th>{{ __('admin.blog.sections.posts') }}</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($sections as $section)
                            {{-- Edited in place: a section is five fields, and
                                 three page loads to rename one is three too
                                 many. Each row is its own form. --}}
                            <tr>
                                <form method="POST" action="{{ lroute('admin.blog.sections.update', $section) }}"
                                      id="section-{{ $section->id }}">
                                    @csrf @method('PUT')
                                </form>

                                <td>
                                    <input class="input" name="name_ar" dir="rtl" required
                                           form="section-{{ $section->id }}" value="{{ $section->name_ar }}">
                                </td>
                                <td>
                                    <input class="input" name="name_en" dir="ltr" required
                                           form="section-{{ $section->id }}" value="{{ $section->name_en }}">
                                </td>
                                <td>
                                    <input class="input" name="key" dir="ltr" required style="width:150px"
                                           form="section-{{ $section->id }}" value="{{ $section->key }}">
                                </td>
                                <td>
                                    <input class="input" type="number" name="sort_order" min="0" style="width:80px"
                                           form="section-{{ $section->id }}" value="{{ $section->sort_order }}">
                                </td>
                                <td>
                                    <label class="check">
                                        <input type="checkbox" name="is_active" value="1"
                                               form="section-{{ $section->id }}" @checked($section->is_active)>
                                        <span class="sr-only">{{ __('admin.blog.sections.active') }}</span>
                                    </label>
                                </td>
                                <td>{{ $section->posts_count }}</td>
                                <td style="text-align:end;white-space:nowrap">
                                    <button type="submit" class="btn btn-secondary btn-sm"
                                            form="section-{{ $section->id }}">
                                        {{ __('admin.blog.save') }}
                                    </button>

                                    @unless ($section->posts_count)
                                        <form method="POST" style="display:inline"
                                              action="{{ lroute('admin.blog.sections.destroy', $section) }}"
                                              x-data
                                              @submit="if (! confirm(@js(__('admin.blog.sections.deleteConfirm')))) $event.preventDefault()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--color-cta)">
                                                {{ __('admin.blog.delete') }}
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

@endsection
