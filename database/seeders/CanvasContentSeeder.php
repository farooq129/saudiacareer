<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Models\JobSeekerPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The sample board: the ten listings and seven seeker posts from the design
 * canvas, with the employers behind them.
 *
 * Its job is to make a fresh install look like a working board rather than an
 * empty one, so the layout can be judged against real Arabic and English copy
 * at real lengths. This is demonstration content, not production data — every
 * employer here is fictional, and the seeder is not part of the production
 * seed (see DatabaseSeeder).
 *
 * Idempotent: it keys on slugs and updates rather than inserting, so running it
 * twice does not double the board.
 */
class CanvasContentSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(database_path('data/canvas-content.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $cities = City::query()->pluck('id', 'key');
        $categories = Category::query()->pluck('id', 'key');
        $types = EmploymentType::query()->pluck('id', 'key');

        if ($cities->isEmpty() || $categories->isEmpty() || $types->isEmpty()) {
            $this->command?->warn('Taxonomies are empty — run the taxonomy seeders first.');

            return;
        }

        $companies = $this->seedCompanies($data['companies'], $cities);

        $this->seedJobs($data['jobs'], $companies, $cities, $categories, $types);
        $this->seedSeekerPosts($data['seekers'], $cities, $categories);

        $this->refreshCounts();
    }

    /** @return array<string, Company> keyed by slug */
    private function seedCompanies(array $rows, $cities): array
    {
        $companies = [];

        foreach ($rows as $row) {
            // Each fictional employer needs an account to own its listings. The
            // phone is derived from the slug so re-running the seeder finds the
            // same user instead of colliding on the unique phone column.
            $user = User::firstOrCreate(
                ['phone' => $this->fakePhone($row['slug'])],
                [
                    'name' => $row['name_en'],
                    'email' => $row['email'],
                    'role' => User::ROLE_EMPLOYER,
                    'password' => 'password',
                    'locale' => 'ar',
                ],
            );

            $company = Company::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'user_id' => $user->id,
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'blurb_ar' => $row['blurb_ar'],
                    'blurb_en' => $row['blurb_en'],
                    'member_since' => $row['member_since'],
                    'whatsapp' => $row['whatsapp'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'city_id' => $cities[$row['city']] ?? null,
                ],
            );

            if ($row['is_verified'] && ! $company->is_verified) {
                $company->forceFill([
                    'is_verified' => true,
                    'verified_at' => now()->subYears(1),
                ])->save();
            }

            $companies[$row['slug']] = $company;
        }

        return $companies;
    }

    private function seedJobs(array $rows, array $companies, $cities, $categories, $types): void
    {
        foreach ($rows as $row) {
            $company = $companies[$row['company_slug']] ?? null;

            if ($company === null) {
                continue;
            }

            // The canvas stores "posted 22 minutes ago" rather than a date, so
            // that the sample board always reads as freshly posted whenever it
            // is seeded. Turn that back into a real timestamp here.
            $publishedAt = now()->subMinutes((int) $row['posted_minutes_ago']);

            Job::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'user_id' => $company->user_id,
                    'company_id' => $company->id,
                    'city_id' => $cities[$row['city']],
                    'category_id' => $categories[$row['category']],
                    'employment_type_id' => $types[$row['employment_type']],

                    'title_ar' => $row['title_ar'],
                    'title_en' => $row['title_en'],
                    'excerpt_ar' => $row['excerpt_ar'],
                    'excerpt_en' => $row['excerpt_en'],
                    'description_ar' => $row['description_ar'],
                    'description_en' => $row['description_en'],

                    'salary_min' => $row['salary_min'],
                    'salary_max' => $row['salary_max'],
                    'salary_note_ar' => $row['salary_note_ar'],
                    'salary_note_en' => $row['salary_note_en'],

                    'experience_ar' => $row['experience_ar'],
                    'experience_en' => $row['experience_en'],
                    'eligibility_ar' => $row['eligibility_ar'],
                    'eligibility_en' => $row['eligibility_en'],
                    'transfer_available' => $row['transfer_available'],

                    'whatsapp' => $row['whatsapp'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],

                    'is_featured' => $row['is_featured'],
                    'is_urgent' => $row['is_urgent'],

                    'status' => Job::STATUS_PUBLISHED,
                    'published_at' => $publishedAt,
                    // The canvas's closing dates are fixed calendar dates that
                    // will drift into the past. Anchor the window to the seed
                    // run instead, so the sample board never expires itself.
                    'expires_at' => $publishedAt->copy()->addDays(config('board.listing.lifetime_days')),

                    'views_count' => $row['views_count'],
                    'applications_count' => $row['applications_count'],
                ],
            );
        }
    }

    private function seedSeekerPosts(array $rows, $cities, $categories): void
    {
        foreach ($rows as $row) {
            $user = User::firstOrCreate(
                ['phone' => $this->fakePhone($row['slug'])],
                [
                    'name' => $row['display_name_en'],
                    'role' => User::ROLE_SEEKER,
                    'password' => 'password',
                    'locale' => 'ar',
                ],
            );

            $publishedAt = now()->subMinutes((int) $row['posted_minutes_ago']);

            JobSeekerPost::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'user_id' => $user->id,
                    'city_id' => $cities[$row['city']],
                    'category_id' => $categories[$row['category']],

                    'display_name_ar' => $row['display_name_ar'],
                    'display_name_en' => $row['display_name_en'],
                    'headline_ar' => $row['headline_ar'],
                    'headline_en' => $row['headline_en'],
                    'pitch_ar' => $row['pitch_ar'],
                    'pitch_en' => $row['pitch_en'],
                    'experience_ar' => $row['experience_ar'],
                    'experience_en' => $row['experience_en'],
                    'transfer_available' => $row['transfer_available'],

                    'status' => JobSeekerPost::STATUS_PUBLISHED,
                    'published_at' => $publishedAt,
                    'expires_at' => $publishedAt->copy()->addDays(config('board.listing.lifetime_days')),
                ],
            );
        }
    }

    /**
     * Bring the cached per-row totals in line with what was just seeded, so the
     * home page's city and category counters are not left showing the canvas's
     * invented figures next to ten real listings.
     */
    private function refreshCounts(): void
    {
        DB::table('cities')->update([
            'jobs_count' => DB::raw(
                '(select count(*) from jobs where jobs.city_id = cities.id '.
                "and jobs.status = 'published' and jobs.deleted_at is null)"
            ),
        ]);

        DB::table('categories')->update([
            'jobs_count' => DB::raw(
                '(select count(*) from jobs where jobs.category_id = categories.id '.
                "and jobs.status = 'published' and jobs.deleted_at is null)"
            ),
        ]);

        DB::table('companies')->update([
            'jobs_count' => DB::raw(
                '(select count(*) from jobs where jobs.company_id = companies.id '.
                "and jobs.status = 'published' and jobs.deleted_at is null)"
            ),
        ]);
    }

    /**
     * A stable, obviously-fake Saudi mobile for a sample account.
     *
     * Derived from the slug so the seeder is idempotent, and kept inside the
     * +96650 range with a hash tail that no real block issues, so a sample row
     * can never collide with a real sign-up.
     */
    private function fakePhone(string $slug): string
    {
        return '+96650'.sprintf('%07d', crc32($slug) % 10_000_000);
    }
}
