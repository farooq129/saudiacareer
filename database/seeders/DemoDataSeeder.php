<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Models\JobReport;
use App\Models\JobSeekerPost;
use App\Models\SavedJob;
use App\Models\User;
use App\Services\ListingModerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * A board with a day's worth of activity on it.
 *
 * CanvasContentSeeder puts up the listings; this puts people in front of them —
 * seekers with accounts, applications at every stage, saved ads, reports in the
 * moderation queue, and listings spread across every status.
 *
 * The point is that every screen in all three areas has something real to show.
 * A dashboard demonstrated against an empty table teaches you nothing about
 * whether it works: you cannot see that the applicant badge counts only unread
 * ones, or that a rejected listing shows its reason back to the employer, until
 * there is a rejected listing with a reason on it.
 *
 * Demonstration data. Every person and employer here is invented. Not part of
 * the default seed — run it explicitly:
 *
 *     php artisan db:seed --class=DemoDataSeeder
 *
 * Idempotent: it keys on phone numbers and on the job/user pair, so running it
 * twice does not double anything.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Seekers who will apply to things. Arabic and English spellings of the
     * same person, because that is how the accounts actually arrive.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const SEEKERS = [
        ['+966551200001', 'خالد المطيري', 'Khalid Al-Mutairi'],
        ['+966551200002', 'منيرة الدوسري', 'Munira Al-Dosari'],
        ['+966551200003', 'عبدالله الغامدي', 'Abdullah Al-Ghamdi'],
        ['+966551200004', 'هند السبيعي', 'Hind Al-Subaie'],
        ['+966551200005', 'ياسر الحربي', 'Yasser Al-Harbi'],
        ['+966551200006', 'ريم العنزي', 'Reem Al-Anazi'],
        ['+966551200007', 'ماجد الشمري', 'Majed Al-Shammari'],
        ['+966551200008', 'أسماء البقمي', 'Asma Al-Buqami'],
        ['+966551200009', 'سلطان القرني', 'Sultan Al-Qarni'],
        ['+966551200010', 'دلال الزهراني', 'Dalal Al-Zahrani'],
        ['+966551200011', 'فهد العتيبي', 'Fahd Al-Otaibi'],
        ['+966551200012', 'لمياء الخالدي', 'Lamya Al-Khalidi'],
    ];

    /** Covering letters, so the employer's inbox is not twelve blank rows. */
    private const LETTERS = [
        'أعمل في نفس المجال منذ خمس سنوات وأبحث عن فرصة أقرب إلى سكني. إقامتي قابلة للنقل وأستطيع المباشرة خلال أسبوعين.',
        'لدي خبرة مباشرة في المهام المذكورة في الإعلان، وسبق أن عملت مع فريق بنفس الحجم. مرفق سيرتي الذاتية.',
        'I have done this exact role for four years and can start at short notice. My iqama is transferable.',
        'أرفقت سيرتي الذاتية وشهاداتي. أرجو النظر في طلبي، ويسعدني الحضور لمقابلة في أي وقت يناسبكم.',
        'Happy to discuss the details on a call. I know the area well and have my own transport.',
        'خبرتي في القطاع نفسه، وأتقن العربية والإنجليزية كتابةً ومحادثة.',
    ];

    public function run(): void
    {
        // The listings themselves come from the canvas set, plus a dozen more
        // that reach the categories it does not.
        $this->call(CanvasContentSeeder::class);
        $this->seedExtraListings();

        $jobs = Job::query()->orderBy('id')->get();

        if ($jobs->isEmpty()) {
            $this->command?->warn('No listings to work with — run CanvasContentSeeder first.');

            return;
        }

        $seekers = $this->seedSeekers();

        $this->spreadListingStatuses($jobs);
        $this->spreadSeekerPostStatuses();

        // Re-read: the status spread changed which listings are live, and only
        // a live listing should be collecting applications.
        $live = Job::query()->published()->orderBy('id')->get();

        $this->seedApplications($live, $seekers);
        $this->seedSavedJobs($live, $seekers);
        $this->seedReports($live, $seekers);

        app(ListingModerator::class)->refreshCounts();

        $this->report();
    }

    /**
     * A dozen more listings, spread across the categories and cities the canvas
     * set leaves empty.
     *
     * Ten listings is enough to judge a layout but not a board: once a
     * realistic spread of statuses takes some out of circulation, almost
     * nothing is left published, and a home page with four ads on it cannot
     * tell you whether the feed works.
     *
     * They are shared out among the existing employers rather than inventing
     * more, so the "all ads by this employer" link has somewhere to go.
     */
    private function seedExtraListings(): void
    {
        $data = json_decode(
            file_get_contents(database_path('data/extra-listings.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $cities = City::query()->pluck('id', 'key');
        $categories = Category::query()->pluck('id', 'key');
        $types = EmploymentType::query()->pluck('id', 'key');
        $companies = Company::query()->orderBy('id')->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($data['jobs'] as $i => $row) {
            $company = $companies[$i % $companies->count()];
            $publishedAt = now()->subHours(6 + ($i * 19));

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

                    'whatsapp' => $company->whatsapp,
                    'phone' => $company->phone,
                    'email' => $company->email,

                    'is_featured' => $row['is_featured'],
                    'is_urgent' => $row['is_urgent'],

                    'status' => Job::STATUS_PUBLISHED,
                    'published_at' => $publishedAt,
                    'expires_at' => $publishedAt->copy()->addDays((int) config('board.listing.lifetime_days')),
                ],
            );
        }
    }

    /** @return Collection<int, User> */
    private function seedSeekers()
    {
        return collect(self::SEEKERS)->map(function (array $row, int $i) {
            [$phone, $nameAr, $nameEn] = $row;

            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    // Arabic name for the Arabic majority; a couple of accounts
                    // registered in English, which is also true to life.
                    'name' => $i % 5 === 0 ? $nameEn : $nameAr,
                    'email' => 'seeker'.($i + 1).'@example.test',
                    'role' => User::ROLE_SEEKER,
                    'password' => 'password',
                    'locale' => $i % 5 === 0 ? 'en' : 'ar',
                    'phone_verified_at' => now()->subDays(3 + ($i * 9)),
                ],
            );

            $user->syncRoles([User::ROLE_SEEKER]);

            // Most have signed in recently; a few never came back after
            // registering, which is what the admin's "last signed in" column
            // is there to show.
            if ($i % 4 !== 3) {
                $user->forceFill([
                    'last_login_at' => now()->subHours(2 + ($i * 31)),
                    'last_login_ip' => '127.0.0.1',
                ])->save();
            }

            return $user;
        });
    }

    /**
     * Put listings into every status.
     *
     * Without this the admin queue is empty and the employer's tabs are all
     * zero but one, so neither screen can be judged.
     */
    private function spreadListingStatuses($jobs): void
    {
        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        // Three waiting on an admin, oldest first in the queue.
        $jobs->slice(0, 3)->each(function (Job $job, int $i) {
            $job->forceFill([
                'status' => Job::STATUS_PENDING,
                'published_at' => null,
                'created_at' => now()->subHours(48 - ($i * 6)),
            ])->save();
        });

        // One turned down, with the reason the employer sees.
        $jobs->slice(3, 1)->each(function (Job $job) use ($admin) {
            $job->forceFill([
                'status' => Job::STATUS_REJECTED,
                'published_at' => null,
                'rejection_reason' => 'الإعلان يطلب رسوم تسجيل من المتقدّم، وهذا مخالف لشروط الاستخدام. احذف الشرط وأعد الإرسال.',
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subHours(20),
            ])->save();
        });

        // One draft the employer never submitted.
        $jobs->slice(4, 1)->each(function (Job $job) {
            $job->forceFill(['status' => Job::STATUS_DRAFT, 'published_at' => null])->save();
        });

        // One filled — the happy ending, and the state that proves a closed
        // vacancy really does leave the board.
        $jobs->slice(5, 1)->each(function (Job $job) use ($admin) {
            $job->forceFill([
                'status' => Job::STATUS_FILLED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDays(2),
            ])->save();
        });

        // The rest stay published, and carry the admin who approved them so the
        // audit trail is not blank.
        $jobs->slice(6)->each(function (Job $job) use ($admin) {
            $job->forceFill([
                'reviewed_by' => $admin?->id,
                'reviewed_at' => $job->published_at,
            ])->save();
        });
    }

    private function spreadSeekerPostStatuses(): void
    {
        $posts = JobSeekerPost::query()->orderBy('id')->get();
        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        $posts->slice(0, 2)->each(function (JobSeekerPost $post, int $i) {
            $post->forceFill([
                'status' => JobSeekerPost::STATUS_PENDING,
                'published_at' => null,
                'created_at' => now()->subHours(30 - ($i * 4)),
            ])->save();
        });

        $posts->slice(2, 1)->each(function (JobSeekerPost $post) use ($admin) {
            $post->forceFill([
                'status' => JobSeekerPost::STATUS_HIRED,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDays(4),
            ])->save();
        });
    }

    /**
     * Applications across every stage, weighted the way a real inbox is: most
     * are new or merely opened, a few shortlisted, one hire.
     */
    private function seedApplications($live, $seekers): void
    {
        if ($live->isEmpty()) {
            return;
        }

        $statuses = [
            Application::STATUS_SENT, Application::STATUS_SENT, Application::STATUS_SENT,
            Application::STATUS_VIEWED, Application::STATUS_VIEWED,
            Application::STATUS_SHORTLISTED,
            Application::STATUS_REJECTED,
            Application::STATUS_HIRED,
        ];

        $n = 0;

        foreach ($live as $jobIndex => $job) {
            // Not every listing gets the same attention — a driver ad pulls
            // dozens, a senior engineering one pulls three. Varying it is what
            // makes the employer dashboard's numbers look like a real week.
            $count = [5, 3, 4, 2, 6, 1, 3, 2][$jobIndex % 8];

            /*
             * Which seekers apply is derived from the job's position, not
             * shuffled. A shuffle would pick a different set on every run, and
             * since firstOrCreate keys on the (job, seeker) pair that means a
             * second run silently adds a second wave of applications instead of
             * finding the ones already there.
             */
            for ($k = 0; $k < $count; $k++) {
                $seeker = $seekers[(($jobIndex * 3) + $k) % $seekers->count()];

                $status = $statuses[$n % count($statuses)];

                // Derived from the pair too, for the same reason — otherwise
                // every run rewrites the timestamps and the feed reorders.
                $sentAt = now()->subHours((($job->id * 7) + ($k * 13)) % 300 + 1);

                $application = Application::firstOrCreate(
                    ['job_id' => $job->id, 'user_id' => $seeker->id],
                    [
                        'source' => $n % 7 === 0 ? 'whatsapp' : 'site',
                        'cover_letter' => $n % 3 === 0 ? null : self::LETTERS[$n % count(self::LETTERS)],
                        'cover_letter_ai_generated' => $n % 9 === 0,
                        'ip_address' => '127.0.0.1',
                    ],
                );

                $application->forceFill([
                    'status' => $status,
                    // Anything past 'sent' has by definition been opened.
                    'viewed_at' => $status === Application::STATUS_SENT
                        ? null
                        : $sentAt->copy()->addHours(($k * 5) + 3),
                    'employer_note' => $status === Application::STATUS_SHORTLISTED
                        ? 'مرشّح قوي — رتّب مقابلة هذا الأسبوع.'
                        : null,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ])->save();

                $n++;
            }
        }

        // The listing's own counter follows only on-site applications, so it is
        // recomputed here rather than incremented per insert.
        foreach ($live as $job) {
            $job->forceFill([
                'applications_count' => Application::query()
                    ->where('job_id', $job->id)
                    ->onSite()
                    ->count(),
                // Derived from the id so a re-run does not keep inflating it.
                'views_count' => 40 + (($job->id * 137) % 860),
            ])->save();
        }
    }

    private function seedSavedJobs($live, $seekers): void
    {
        // Derived from position rather than shuffled, for the same reason as
        // the applications: a random pick means a second run saves a different
        // set of ads on top of the first.
        foreach ($seekers->take(8) as $i => $seeker) {
            for ($k = 0; $k < ($i % 4) + 1; $k++) {
                $job = $live[(($i * 5) + $k) % $live->count()];

                SavedJob::firstOrCreate(['user_id' => $seeker->id, 'job_id' => $job->id]);
            }
        }
    }

    /**
     * Reports, including resolved ones — an admin screen that only ever shows
     * an open queue hides half of what it is for.
     */
    private function seedReports($live, $seekers): void
    {
        if ($live->isEmpty()) {
            return;
        }

        $admin = User::query()->where('role', User::ROLE_ADMIN)->first();

        $rows = [
            ['fees', 'طلبوا مني تحويل 300 ريال مقابل إجراءات التوظيف.', JobReport::STATUS_OPEN, null],
            ['fake', 'الشركة غير موجودة على هذا العنوان، والرقم لا يرد.', JobReport::STATUS_OPEN, null],
            ['duplicate', 'نفس الإعلان منشور ثلاث مرات بمسميات مختلفة.', JobReport::STATUS_OPEN, null],
            ['filled', 'اتصلت بهم وقالوا إن الوظيفة أُشغلت الشهر الماضي.', JobReport::STATUS_DISMISSED, 'تم التواصل مع صاحب العمل وأكّد أن الإعلان ما زال سارياً.'],
            ['wrong_category', 'الوظيفة إدارية وليست في قسم السائقين.', JobReport::STATUS_UPHELD, 'تم نقل الإعلان إلى القسم الصحيح.'],
        ];

        foreach ($rows as $i => [$reason, $note, $status, $resolution]) {
            $job = $live[$i % $live->count()];

            $report = JobReport::firstOrCreate(
                ['job_id' => $job->id, 'reason' => $reason],
                [
                    // Some reports come from signed-out visitors, which is the
                    // case the nullable user_id exists for.
                    'user_id' => $i % 2 === 0 ? $seekers[$i % $seekers->count()]->id : null,
                    'note' => $note,
                    'ip_address' => '127.0.0.1',
                ],
            );

            $report->forceFill([
                'status' => $status,
                'resolution_note' => $resolution,
                'resolved_by' => $resolution ? $admin?->id : null,
                'resolved_at' => $resolution ? now()->subDays($i) : null,
                'created_at' => now()->subDays(($i * 2) + 1),
            ])->save();
        }
    }

    private function report(): void
    {
        $this->command?->info(sprintf(
            'Demo board ready — %d users, %d employers, %d listings, %d seeker posts, %d applications, %d saved, %d reports.',
            User::count(),
            User::where('role', User::ROLE_EMPLOYER)->count(),
            Job::count(),
            JobSeekerPost::count(),
            Application::count(),
            SavedJob::count(),
            JobReport::count(),
        ));
    }
}
