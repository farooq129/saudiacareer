<?php

namespace App\Http\Requests\Employer;

use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Posting or editing a vacancy.
 *
 * Both languages are required for the title. A board that lets one side go
 * blank ends up with half its listings invisible to half its audience, and the
 * employer is the only person who can write the other one — nobody downstream
 * will go back and do it.
 *
 * Everything past the title is optional in one language and falls back to the
 * other at render time (see HasTranslatableColumns), because demanding a full
 * bilingual job description up front is how you lose the employer.
 */
class JobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_ar' => ['required', 'string', 'max:200'],
            'title_en' => ['required', 'string', 'max:200'],

            'city_id' => ['required', Rule::exists(City::class, 'id')->where('is_active', true)],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'employment_type_id' => ['required', Rule::exists(EmploymentType::class, 'id')->where('is_active', true)],

            'excerpt_ar' => ['nullable', 'string', 'max:500'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],

            // The body arrives as a textarea, one paragraph per line, and is
            // split into the JSON array the schema stores.
            'description_ar' => ['nullable', 'string', 'max:8000'],
            'description_en' => ['nullable', 'string', 'max:8000'],

            'salary_min' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:1000000', 'gte:salary_min'],
            'salary_note_ar' => ['nullable', 'string', 'max:255'],
            'salary_note_en' => ['nullable', 'string', 'max:255'],

            'experience_ar' => ['nullable', 'string', 'max:80'],
            'experience_en' => ['nullable', 'string', 'max:80'],
            'eligibility_ar' => ['nullable', 'string', 'max:120'],
            'eligibility_en' => ['nullable', 'string', 'max:120'],
            'transfer_available' => ['nullable', 'boolean'],

            'whatsapp' => ['nullable', 'string', 'max:25'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title_ar' => __('employer.form.titleAr'),
            'title_en' => __('employer.form.titleEn'),
            'city_id' => __('search.city'),
            'category_id' => __('search.category'),
            'employment_type_id' => __('search.employmentType'),
        ];
    }

    /**
     * Turn the line-per-item textareas into the JSON arrays the schema holds.
     *
     * @return array<string, list<string>>
     */
    public function listFields(): array
    {
        $out = [];

        foreach (['description'] as $base) {
            foreach (['ar', 'en'] as $lang) {
                $key = $base.'_'.$lang;
                $raw = (string) $this->input($key, '');

                $lines = array_values(array_filter(
                    array_map('trim', preg_split('/\R/u', $raw) ?: []),
                    fn (string $line) => $line !== '',
                ));

                $out[$key] = $lines;
            }
        }

        return $out;
    }
}
