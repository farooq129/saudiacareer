<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Company;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The employer's own organisation profile.
 *
 * Two fields are deliberately not editable here: `is_verified`, which only an
 * admin grants, and `slug`, which is the company's public URL and must not
 * change under links that already exist.
 */
class CompanyController extends Controller
{
    public function edit(Request $request): View
    {
        return view('employer.company.edit', [
            'company' => $request->user()->company ?? new Company,
            'cities' => City::active()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company;

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:180'],
            'name_en' => ['required', 'string', 'max:180'],
            'blurb_ar' => ['nullable', 'string', 'max:1000'],
            'blurb_en' => ['nullable', 'string', 'max:1000'],
            'city_id' => ['nullable', Rule::exists(City::class, 'id')],
            'website' => ['nullable', 'url', 'max:255'],
            'cr_number' => ['nullable', 'string', 'max:40'],
            'staff_count' => ['nullable', 'integer', 'min:1', 'max:60000'],
            'member_since' => ['nullable', 'integer', 'min:1950', 'max:'.date('Y')],
            'whatsapp' => ['nullable', 'string', 'max:25'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $data['whatsapp'] = Phone::normalise($data['whatsapp'] ?? null) ?? ($data['whatsapp'] ?? null);
        $data['phone'] = Phone::normalise($data['phone'] ?? null) ?? ($data['phone'] ?? null);

        if ($company === null) {
            $company = new Company(['user_id' => $user->id]);
            $company->user_id = $user->id;
            $company->slug = $this->uniqueSlug($data['name_en']);
        }

        $company->fill($data);
        $company->save();

        /*
         * A change to the registered name or CR number invalidates the badge:
         * what an admin checked was the old name. Leaving the tick on a
         * renamed company is exactly how a verified shell gets resold.
         */
        if ($company->is_verified && $company->wasChanged(['name_ar', 'name_en', 'cr_number'])) {
            $company->forceFill([
                'is_verified' => false,
                'verified_at' => null,
                'verified_by' => null,
            ])->save();

            return back()->with('status', __('employer.flash.companySavedReverify'));
        }

        return back()->with('status', __('employer.flash.companySaved'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'employer';
        $slug = $base;
        $n = 1;

        while (Company::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$n;
        }

        return $slug;
    }
}
