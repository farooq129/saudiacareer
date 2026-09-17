<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Writing or editing an article.
 *
 * Both headlines are required, for the same reason the job form demands both
 * titles: a post with one side blank is invisible to half the audience, and the
 * author is the only person who will ever write the other one.
 *
 * Everything past the headline is optional in one language and falls back to
 * the other at render time (see HasTranslatableColumns).
 */
class BlogPostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_ar' => ['required', 'string', 'max:200'],
            'title_en' => ['required', 'string', 'max:200'],

            // A hand-written slug is allowed on edit so a published URL can be
            // corrected, but it stays language-neutral and unique.
            'slug' => [
                'nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(BlogPost::class, 'slug')->ignore($this->route('post')?->id),
            ],

            'blog_category_id' => ['nullable', Rule::exists(BlogCategory::class, 'id')->where('is_active', true)],
            'author_name' => ['nullable', 'string', 'max:120'],

            'excerpt_ar' => ['nullable', 'string', 'max:500'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],

            // The body arrives as a textarea, one paragraph per line, and is
            // split into the JSON array the schema stores.
            'body_ar' => ['nullable', 'string', 'max:40000'],
            'body_en' => ['nullable', 'string', 'max:40000'],

            // One image per post. Capped at 4MB and to real raster formats —
            // an SVG is a script container, not a photo.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'image_alt_ar' => ['nullable', 'string', 'max:255'],
            'image_alt_en' => ['nullable', 'string', 'max:255'],

            /* ── SEO ────────────────────────────────────────────────────── */
            // Lengths match what a result list actually shows before it cuts.
            'meta_title_ar' => ['nullable', 'string', 'max:180'],
            'meta_title_en' => ['nullable', 'string', 'max:180'],
            'meta_description_ar' => ['nullable', 'string', 'max:320'],
            'meta_description_en' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'is_indexable' => ['nullable', 'boolean'],

            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([BlogPost::STATUS_DRAFT, BlogPost::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title_ar' => __('admin.blog.form.titleAr'),
            'title_en' => __('admin.blog.form.titleEn'),
            'blog_category_id' => __('admin.blog.form.section'),
            'image' => __('admin.blog.form.image'),
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => __('admin.blog.form.slugRule'),
        ];
    }

    /**
     * Turn the line-per-paragraph textareas into the JSON arrays the schema
     * holds, matching how the job description field is handled.
     *
     * @return array<string, list<string>>
     */
    public function bodyParagraphs(): array
    {
        $out = [];

        foreach (['ar', 'en'] as $lang) {
            $key = 'body_'.$lang;

            $out[$key] = array_values(array_filter(
                array_map('trim', preg_split('/\R/u', (string) $this->input($key, '')) ?: []),
                fn (string $line) => $line !== '',
            ));
        }

        return $out;
    }
}
