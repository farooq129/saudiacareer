<?php

namespace App\Http\Requests\Seeker;

use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A seeker advertising themselves.
 *
 * Lighter than the employer's form on purpose: the person filling this in is
 * often on a phone, often between jobs, and every extra required field is
 * someone who does not finish. Name and headline in both languages, a city and
 * a category, and everything else optional.
 */
class PostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'display_name_ar' => ['required', 'string', 'max:160'],
            'display_name_en' => ['required', 'string', 'max:160'],
            'headline_ar' => ['required', 'string', 'max:200'],
            'headline_en' => ['required', 'string', 'max:200'],

            'city_id' => ['required', Rule::exists(City::class, 'id')->where('is_active', true)],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'employment_type_id' => ['nullable', Rule::exists(EmploymentType::class, 'id')],

            'pitch_ar' => ['nullable', 'string', 'max:1000'],
            'pitch_en' => ['nullable', 'string', 'max:1000'],
            'experience_ar' => ['nullable', 'string', 'max:80'],
            'experience_en' => ['nullable', 'string', 'max:80'],

            'expected_salary_min' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'expected_salary_max' => ['nullable', 'integer', 'min:0', 'max:1000000', 'gte:expected_salary_min'],

            'transfer_available' => ['nullable', 'boolean'],
            'available_immediately' => ['nullable', 'boolean'],

            'whatsapp' => ['nullable', 'string', 'max:25'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],

            // 4 MB is about the largest a scanned two-page CV gets, and is
            // small enough to upload on a phone connection.
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'display_name_ar' => __('seeker.form.nameAr'),
            'display_name_en' => __('seeker.form.nameEn'),
            'headline_ar' => __('seeker.form.headlineAr'),
            'headline_en' => __('seeker.form.headlineEn'),
            'city_id' => __('search.city'),
            'category_id' => __('search.category'),
        ];
    }
}
