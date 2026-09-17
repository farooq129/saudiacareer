@extends('layouts.portal')

@php
    use App\Models\BlogPost;

    $editing = $post->exists;
    $action = $editing ? lroute('admin.blog.update', $post) : lroute('admin.blog.store');

    /*
     * The body is stored as a JSON array and edited one paragraph per line, so
     * it is flattened on the way in. old() wins after a failed validation pass,
     * which is why it is checked first.
     */
    $lines = function (string $key) use ($post) {
        if (old($key) !== null) {
            return old($key);
        }

        $value = $post->{$key};

        return is_array($value) ? implode("\n", $value) : (string) $value;
    };
@endphp

@section('title', $editing ? __('admin.blog.editTitle') : __('admin.blog.newTitle'))
@section('heading', $editing ? __('admin.blog.editTitle') : __('admin.blog.newTitle'))

@section('content')

    <form method="POST" action="{{ $action }}" class="adm-detail" style="align-items:start"
          enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div>
            {{-- ── The article ──────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('admin.blog.form.article') }}</h5>

                <p class="text-muted" style="font-size:13px;margin-bottom:var(--space-6)">
                    {{ __('admin.blog.form.bothLanguages') }}
                </p>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="title_ar">{{ __('admin.blog.form.titleAr') }} *</label>
                        <input id="title_ar" class="input" name="title_ar" dir="rtl" required
                               value="{{ old('title_ar', $post->title_ar) }}">
                        @error('title_ar') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="title_en">{{ __('admin.blog.form.titleEn') }} *</label>
                        <input id="title_en" class="input" name="title_en" dir="ltr" required
                               value="{{ old('title_en', $post->title_en) }}">
                        @error('title_en') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="excerpt_ar">{{ __('admin.blog.form.excerptAr') }}</label>
                        <textarea id="excerpt_ar" class="input" name="excerpt_ar" dir="rtl" rows="2">{{ old('excerpt_ar', $post->excerpt_ar) }}</textarea>
                        <span class="field-hint">{{ __('admin.blog.form.excerptHint') }}</span>
                    </div>

                    <div class="field">
                        <label for="excerpt_en">{{ __('admin.blog.form.excerptEn') }}</label>
                        <textarea id="excerpt_en" class="input" name="excerpt_en" dir="ltr" rows="2">{{ old('excerpt_en', $post->excerpt_en) }}</textarea>
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="body_ar">{{ __('admin.blog.form.bodyAr') }}</label>
                        <textarea id="body_ar" class="input" name="body_ar" dir="rtl" rows="16">{{ $lines('body_ar') }}</textarea>
                        <span class="field-hint">{{ __('admin.blog.form.bodyHint') }}</span>
                    </div>

                    <div class="field">
                        <label for="body_en">{{ __('admin.blog.form.bodyEn') }}</label>
                        <textarea id="body_en" class="input" name="body_en" dir="ltr" rows="16">{{ $lines('body_en') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ── The image ────────────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('admin.blog.form.image') }}</h5>

                @if ($post->image_path)
                    <div class="adm-image-current">
                        <img src="{{ asset($post->image_path) }}" alt="">
                        <label class="check">
                            <input type="checkbox" name="remove_image" value="1">
                            <span>{{ __('admin.blog.form.removeImage') }}</span>
                        </label>
                    </div>
                @endif

                <div class="field">
                    <label for="image">
                        {{ $post->image_path ? __('admin.blog.form.replaceImage') : __('admin.blog.form.uploadImage') }}
                    </label>
                    <input id="image" class="input" type="file" name="image"
                           accept="image/jpeg,image/png,image/webp">
                    <span class="field-hint">{{ __('admin.blog.form.imageHint') }}</span>
                    @error('image') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="image_alt_ar">{{ __('admin.blog.form.altAr') }}</label>
                        <input id="image_alt_ar" class="input" name="image_alt_ar" dir="rtl"
                               value="{{ old('image_alt_ar', $post->image_alt_ar) }}">
                    </div>

                    <div class="field">
                        <label for="image_alt_en">{{ __('admin.blog.form.altEn') }}</label>
                        <input id="image_alt_en" class="input" name="image_alt_en" dir="ltr"
                               value="{{ old('image_alt_en', $post->image_alt_en) }}">
                    </div>
                </div>

                <span class="field-hint">{{ __('admin.blog.form.altHint') }}</span>
            </div>

            {{-- ── Search engines ───────────────────────────────────────── --}}
            <div class="adm-block">
                <h5>{{ __('admin.blog.form.seo') }}</h5>

                <p class="text-muted" style="font-size:13px;margin-bottom:var(--space-6)">
                    {{ __('admin.blog.form.seoHint') }}
                </p>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="meta_title_ar">{{ __('admin.blog.form.metaTitleAr') }}</label>
                        <input id="meta_title_ar" class="input" name="meta_title_ar" dir="rtl" maxlength="180"
                               value="{{ old('meta_title_ar', $post->meta_title_ar) }}">
                    </div>

                    <div class="field">
                        <label for="meta_title_en">{{ __('admin.blog.form.metaTitleEn') }}</label>
                        <input id="meta_title_en" class="input" name="meta_title_en" dir="ltr" maxlength="180"
                               value="{{ old('meta_title_en', $post->meta_title_en) }}">
                    </div>
                </div>

                <div class="adm-bilingual">
                    <div class="field">
                        <label for="meta_description_ar">{{ __('admin.blog.form.metaDescAr') }}</label>
                        <textarea id="meta_description_ar" class="input" name="meta_description_ar" dir="rtl"
                                  rows="2" maxlength="320">{{ old('meta_description_ar', $post->meta_description_ar) }}</textarea>
                    </div>

                    <div class="field">
                        <label for="meta_description_en">{{ __('admin.blog.form.metaDescEn') }}</label>
                        <textarea id="meta_description_en" class="input" name="meta_description_en" dir="ltr"
                                  rows="2" maxlength="320">{{ old('meta_description_en', $post->meta_description_en) }}</textarea>
                    </div>
                </div>

                <div class="field">
                    <label for="canonical_url">{{ __('admin.blog.form.canonical') }}</label>
                    <input id="canonical_url" class="input" name="canonical_url" dir="ltr" type="url"
                           value="{{ old('canonical_url', $post->canonical_url) }}">
                    <span class="field-hint">{{ __('admin.blog.form.canonicalHint') }}</span>
                    @error('canonical_url') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <label class="check">
                    <input type="checkbox" name="is_indexable" value="1"
                           @checked(old('is_indexable', $post->is_indexable ?? true))>
                    <span>{{ __('admin.blog.form.indexable') }}</span>
                </label>
            </div>
        </div>

        {{-- ── Publishing ───────────────────────────────────────────────── --}}
        <aside class="adm-block">
            <h5>{{ __('admin.blog.form.publishing') }}</h5>

            <div class="field">
                <label for="status">{{ __('admin.listing.status') }}</label>
                <select id="status" class="input" name="status">
                    <option value="draft" @selected(old('status', $post->status) === BlogPost::STATUS_DRAFT)>
                        {{ __('admin.blog.status.draft') }}
                    </option>
                    <option value="published" @selected(old('status', $post->status) === BlogPost::STATUS_PUBLISHED)>
                        {{ __('admin.blog.status.published') }}
                    </option>
                </select>
            </div>

            <div class="field">
                <label for="published_at">{{ __('admin.blog.form.publishDate') }}</label>
                <input id="published_at" class="input" type="datetime-local" name="published_at"
                       value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                <span class="field-hint">{{ __('admin.blog.form.publishDateHint') }}</span>
            </div>

            <div class="field">
                <label for="blog_category_id">{{ __('admin.blog.form.section') }}</label>
                <select id="blog_category_id" class="input" name="blog_category_id">
                    <option value="">{{ __('admin.blog.form.noSection') }}</option>
                    @foreach ($sections as $item)
                        <option value="{{ $item->id }}"
                            @selected((int) old('blog_category_id', $post->blog_category_id) === $item->id)>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="author_name">{{ __('admin.blog.form.byline') }}</label>
                <input id="author_name" class="input" name="author_name"
                       value="{{ old('author_name', $post->author_name) }}">
                <span class="field-hint">{{ __('admin.blog.form.bylineHint') }}</span>
            </div>

            @if ($editing)
                <div class="field">
                    <label for="slug">{{ __('admin.blog.form.slug') }}</label>
                    <input id="slug" class="input" name="slug" dir="ltr"
                           value="{{ old('slug', $post->slug) }}">
                    <span class="field-hint">{{ __('admin.blog.form.slugHint') }}</span>
                    @error('slug') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            @endif

            <label class="check">
                <input type="checkbox" name="is_featured" value="1"
                       @checked(old('is_featured', $post->is_featured))>
                <span>{{ __('admin.blog.form.featured') }}</span>
            </label>

            <button type="submit" class="btn-blue" style="width:100%;margin-top:var(--space-6)">
                {{ $editing ? __('admin.blog.save') : __('admin.blog.create') }}
            </button>
        </aside>
    </form>

    @if ($editing)
        <div class="adm-block" style="margin-top:var(--space-6)">
            <h5>{{ __('admin.blog.form.actions') }}</h5>

            <div style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:center">
                @if ($post->status === BlogPost::STATUS_PUBLISHED)
                    <form method="POST" action="{{ lroute('admin.blog.unpublish', $post) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-secondary">{{ __('admin.blog.unpublish') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ lroute('admin.blog.publish', $post) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn-blue">{{ __('admin.blog.publishNow') }}</button>
                    </form>
                @endif

                <form method="POST" action="{{ lroute('admin.blog.destroy', $post) }}"
                      x-data @submit="if (! confirm(@js(__('admin.blog.deleteConfirm')))) $event.preventDefault()">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary" style="color:var(--color-cta)">{{ __('admin.blog.delete') }}</button>
                </form>
            </div>
        </div>
    @endif

@endsection
