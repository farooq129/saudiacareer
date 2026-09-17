<?php

namespace App\Http\Requests\Admin;

use App\Models\BlogCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // The key appears in a crawlable URL, so it is constrained to the
            // same shape as a post slug and cannot be changed casually.
            'key' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(BlogCategory::class, 'key')->ignore($this->route('blogCategory')?->id),
            ],
            'name_ar' => ['required', 'string', 'max:120'],
            'name_en' => ['required', 'string', 'max:120'],
            'blurb_ar' => ['nullable', 'string', 'max:255'],
            'blurb_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['key.regex' => __('admin.blog.form.slugRule')];
    }
}
