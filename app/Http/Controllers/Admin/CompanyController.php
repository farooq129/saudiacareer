<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Employers, and the verified badge.
 *
 * The badge is the load-bearing part: it appears on every card and every
 * listing detail, and it is the only signal a seeker has that someone checked
 * this employer is real. So it is granted by an admin against a commercial
 * registration number, never by the employer themselves, and who granted it is
 * recorded.
 */
class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'verified' => $request->query('verified', 'all'),
        ];

        $companies = Company::query()
            ->with(['user', 'city', 'verifier'])
            ->withCount([
                'jobs',
                'jobs as live_jobs_count' => fn ($q) => $q->published(),
            ])
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';

                $q->where(fn ($w) => $w
                    ->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('cr_number', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($filters['verified'] === 'yes', fn ($q) => $q->where('is_verified', true))
            ->when($filters['verified'] === 'no', fn ($q) => $q->where('is_verified', false))
            // Unverified first: that is the list an admin is here to work
            // through, and a verified employer needs no attention.
            ->orderBy('is_verified')
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.companies.index', [
            'companies' => $companies,
            'filters' => $filters,
        ]);
    }

    public function show(Company $company): View
    {
        $company->load(['user', 'city', 'verifier']);
        $company->loadCount(['jobs', 'jobs as live_jobs_count' => fn ($q) => $q->published()]);

        return view('admin.companies.show', [
            'company' => $company,
            'jobs' => $company->jobs()
                ->with(['city', 'category'])
                ->latest('created_at')
                ->take(20)
                ->get(),
        ]);
    }

    public function verify(Request $request, Company $company): RedirectResponse
    {
        $company->forceFill([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ])->save();

        return back()->with('status', __('admin.flash.verified'));
    }

    /**
     * Withdraw the badge. verified_at and verified_by are cleared with it —
     * a stale "verified by X on Y" against an employer who is no longer
     * verified is worse than no record at all.
     */
    public function unverify(Company $company): RedirectResponse
    {
        $company->forceFill([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ])->save();

        return back()->with('status', __('admin.flash.unverified'));
    }
}
