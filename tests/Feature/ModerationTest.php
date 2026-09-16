<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\EmploymentTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The approve/reject cycle, and the ownership rules around it.
 *
 * This is the part of the product where a mistake is expensive: a listing that
 * goes live without review, or an employer who can see another employer's
 * applicants, is worse than any layout bug.
 */
class ModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CitySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(EmploymentTypeSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin', 'phone' => '+966550000001',
            'password' => 'secret-password', 'role' => User::ROLE_ADMIN,
        ]);

        $this->employer = User::create([
            'name' => 'Employer', 'phone' => '+966550000002',
            'password' => 'secret-password', 'role' => User::ROLE_EMPLOYER,
        ]);
    }

    public function test_a_new_listing_waits_for_review_rather_than_going_live(): void
    {
        config(['board.listing.moderated' => true]);

        $this->actingAs($this->employer)
            ->post('/employer/jobs', $this->jobPayload())
            ->assertRedirect();

        $job = Job::firstWhere('title_en', 'Warehouse Supervisor');

        $this->assertSame(Job::STATUS_PENDING, $job->status);
        $this->assertNull($job->published_at);

        // And it is genuinely not on the board.
        $this->get('/jobs/'.$job->slug)->assertNotFound();
    }

    public function test_an_admin_approving_it_puts_it_on_the_board(): void
    {
        $job = $this->pendingJob();

        $this->actingAs($this->admin)
            ->patch('/admin/jobs/'.$job->slug.'/approve')
            ->assertRedirect();

        $job->refresh();

        $this->assertSame(Job::STATUS_PUBLISHED, $job->status);
        $this->assertNotNull($job->published_at);
        // The window opens now, not at whatever date the draft carried.
        $this->assertTrue($job->expires_at->isFuture());
        // Who approved it has to be answerable months later.
        $this->assertSame($this->admin->id, $job->reviewed_by);

        $this->get('/jobs/'.$job->slug)->assertOk();
    }

    public function test_a_rejection_must_carry_a_reason_and_shows_it_to_the_poster(): void
    {
        $job = $this->pendingJob();

        // No reason, no rejection.
        $this->actingAs($this->admin)
            ->patch('/admin/jobs/'.$job->slug.'/reject', ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Job::STATUS_PENDING, $job->fresh()->status);

        $this->actingAs($this->admin)
            ->patch('/admin/jobs/'.$job->slug.'/reject', [
                'reason' => 'The salary figure contradicts the description.',
            ])
            ->assertRedirect();

        $job->refresh();
        $this->assertSame(Job::STATUS_REJECTED, $job->status);
        $this->assertNull($job->published_at);

        // The employer sees the reason on their own list.
        $this->actingAs($this->employer)
            ->get('/employer/jobs?status=rejected')
            ->assertOk()
            ->assertSee('The salary figure contradicts the description.');
    }

    public function test_editing_a_live_listing_sends_it_back_for_review(): void
    {
        config(['board.listing.moderated' => true]);

        $job = $this->pendingJob();
        $this->actingAs($this->admin)->patch('/admin/jobs/'.$job->slug.'/approve');

        $this->assertSame(Job::STATUS_PUBLISHED, $job->fresh()->status);

        // Otherwise an employer could get an innocuous ad approved and then
        // rewrite it into whatever they liked.
        $this->actingAs($this->employer)
            ->put('/employer/jobs/'.$job->slug, $this->jobPayload([
                'title_en' => 'Something Else Entirely',
            ]))
            ->assertRedirect();

        $this->assertSame(Job::STATUS_PENDING, $job->fresh()->status);
        $this->get('/jobs/'.$job->slug)->assertNotFound();
    }

    public function test_one_employer_cannot_touch_another_employers_listing(): void
    {
        $job = $this->pendingJob();

        $intruder = User::create([
            'name' => 'Other', 'phone' => '+966550000003',
            'password' => 'secret-password', 'role' => User::ROLE_EMPLOYER,
        ]);

        $this->actingAs($intruder)->get('/employer/jobs/'.$job->slug.'/edit')->assertNotFound();
        $this->actingAs($intruder)->delete('/employer/jobs/'.$job->slug)->assertNotFound();
    }

    public function test_only_an_admin_reaches_the_admin_area(): void
    {
        $this->actingAs($this->employer)->get('/admin')->assertRedirect();
        $this->actingAs($this->employer)->get('/admin/jobs')->assertRedirect();
        $this->actingAs($this->admin)->get('/admin')->assertOk();
    }

    public function test_bulk_approval_clears_several_at_once(): void
    {
        $ids = collect(range(1, 3))
            ->map(fn (int $n) => $this->pendingJob('Bulk Role '.$n)->id)
            ->all();

        $this->actingAs($this->admin)
            ->post('/admin/jobs/bulk', ['action' => 'approve', 'ids' => $ids])
            ->assertRedirect();

        $this->assertSame(0, Job::query()->awaitingReview()->count());
        $this->assertSame(3, Job::query()->published()->count());
    }

    public function test_a_bulk_rejection_still_needs_a_reason(): void
    {
        $job = $this->pendingJob();

        $this->actingAs($this->admin)
            ->post('/admin/jobs/bulk', ['action' => 'reject', 'ids' => [$job->id]])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Job::STATUS_PENDING, $job->fresh()->status);
    }

    /**
     * A listing with no excerpt used to leak an output buffer on every render:
     * Blade reads a null second argument to @section as the block form and
     * calls ob_start() with nothing to close it. PHPUnit catches this as a
     * risky test, which is the only reason it was ever noticed.
     */
    public function test_a_listing_with_no_excerpt_renders_without_leaking_a_buffer(): void
    {
        $job = $this->pendingJob();
        $this->actingAs($this->admin)->patch('/admin/jobs/'.$job->slug.'/approve');

        $depth = ob_get_level();

        $this->get('/jobs/'.$job->slug)->assertOk();

        $this->assertSame($depth, ob_get_level(), 'Rendering the listing left an output buffer open.');
    }

    /**
     * Laravel redirects guests to a bare `route('login')` and signed-in users
     * to `/dashboard`. Neither name exists here, because every route is
     * registered twice under `ar.` and `en.` — so both defaults threw a
     * RouteNotFoundException and served a 500 where a redirect belonged. The
     * replacements live in bootstrap/app.php.
     */
    public function test_a_guest_is_sent_to_sign_in_in_their_own_language(): void
    {
        $this->get('/admin/reports')->assertRedirect(route('ar.login'));
        $this->get('/en/admin/reports?status=open')->assertRedirect(route('en.login'));

        $this->get('/employer')->assertRedirect(route('ar.login'));
        $this->get('/en/seeker/posts')->assertRedirect(route('en.login'));
    }

    public function test_signing_in_returns_you_to_the_page_you_asked_for(): void
    {
        $this->get('/en/admin/reports?status=open')->assertRedirect(route('en.login'));

        $this->post('/en/login', [
            'phone' => $this->admin->phone,
            'password' => 'secret-password',
        ])->assertRedirect('http://localhost:8000/en/admin/reports?status=open');
    }

    public function test_a_signed_in_user_is_sent_to_their_own_area_not_a_dashboard_that_does_not_exist(): void
    {
        $this->actingAs($this->admin)->get('/login')->assertRedirect(route('ar.admin.dashboard'));
        $this->actingAs($this->employer)->get('/en/register')->assertRedirect(route('en.employer.dashboard'));
    }

    /** @return array<string, mixed> */
    private function jobPayload(array $overrides = []): array
    {
        return array_merge([
            'title_ar' => 'مشرف مستودع',
            'title_en' => 'Warehouse Supervisor',
            'city_id' => City::where('key', 'riyadh')->value('id'),
            'category_id' => Category::where('key', 'logistics')->value('id'),
            'employment_type_id' => EmploymentType::where('key', 'full')->value('id'),
            'description_en' => "First paragraph.\nSecond paragraph.",
            'intent' => 'submit',
        ], $overrides);
    }

    private function pendingJob(string $title = 'Warehouse Supervisor'): Job
    {
        $company = Company::firstOrCreate(
            ['slug' => 'test-co'],
            ['user_id' => $this->employer->id, 'name_ar' => 'شركة', 'name_en' => 'Test Co.'],
        );

        return Job::create([
            'user_id' => $this->employer->id,
            'company_id' => $company->id,
            'city_id' => City::where('key', 'riyadh')->value('id'),
            'category_id' => Category::where('key', 'logistics')->value('id'),
            'employment_type_id' => EmploymentType::where('key', 'full')->value('id'),
            'slug' => Str::slug($title),
            'title_ar' => 'مشرف مستودع',
            'title_en' => $title,
            'status' => Job::STATUS_PENDING,
        ]);
    }
}
