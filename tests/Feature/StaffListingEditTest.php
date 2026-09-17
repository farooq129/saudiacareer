<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Models\JobSeekerPost;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\EmploymentTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admins and moderators correcting a listing in place.
 *
 * The load-bearing assertion here is that a staff edit does NOT bounce a live
 * listing back into the review queue. An employer's own edit does, and must:
 * otherwise an approved ad could be rewritten into anything. Staff are the
 * people that rule protects against, so applying it to them would unpublish
 * the listing a moderator had just finished fixing.
 */
class StaffListingEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $moderator;

    private User $employer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CitySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(EmploymentTypeSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin', 'phone' => '+966550000201',
            'password' => 'secret-password', 'role' => User::ROLE_ADMIN,
        ]);

        $this->moderator = User::create([
            'name' => 'Moderator', 'phone' => '+966550000202',
            'password' => 'secret-password', 'role' => User::ROLE_MODERATOR,
        ]);

        $this->employer = User::create([
            'name' => 'Employer', 'phone' => '+966550000203',
            'password' => 'secret-password', 'role' => User::ROLE_EMPLOYER,
        ]);
    }

    private function job(array $overrides = []): Job
    {
        return Job::create(array_merge([
            'user_id' => $this->employer->id,
            'city_id' => City::first()->id,
            'category_id' => Category::first()->id,
            'employment_type_id' => EmploymentType::first()->id,
            'slug' => 'a-listing',
            'title_ar' => 'سائق', 'title_en' => 'Driver',
            'status' => Job::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /** The payload the shared form posts back. */
    private function jobPayload(array $overrides = []): array
    {
        return array_merge([
            'title_ar' => 'سائق', 'title_en' => 'Driver',
            'city_id' => City::first()->id,
            'category_id' => Category::first()->id,
            'employment_type_id' => EmploymentType::first()->id,
        ], $overrides);
    }

    private function seekerPost(array $overrides = []): JobSeekerPost
    {
        return JobSeekerPost::create(array_merge([
            'user_id' => $this->employer->id,
            'city_id' => City::first()->id,
            'category_id' => Category::first()->id,
            'slug' => 'a-seeker',
            'display_name_ar' => 'محمد', 'display_name_en' => 'Mohammed',
            'headline_ar' => 'سائق خاص', 'headline_en' => 'Private driver',
            'status' => JobSeekerPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /* ── Reach ──────────────────────────────────────────────────────────── */

    public function test_an_admin_can_open_the_listing_edit_form(): void
    {
        $job = $this->job();

        $this->actingAs($this->admin)
            ->get('/en/admin/jobs/'.$job->slug.'/edit')
            ->assertOk()
            ->assertSee('Driver', false);
    }

    public function test_a_moderator_can_open_both_edit_forms(): void
    {
        $job = $this->job();
        $post = $this->seekerPost();

        $this->actingAs($this->moderator)->get('/en/admin/jobs/'.$job->slug.'/edit')->assertOk();
        $this->actingAs($this->moderator)->get('/en/admin/seekers/'.$post->slug.'/edit')->assertOk();
    }

    public function test_an_employer_cannot_reach_the_staff_edit_form(): void
    {
        $job = $this->job();

        $this->actingAs($this->employer)
            ->get('/en/admin/jobs/'.$job->slug.'/edit')
            ->assertRedirect()
            ->assertSessionHasErrors('access');
    }

    public function test_a_guest_cannot_reach_the_staff_edit_form(): void
    {
        $job = $this->job();

        $this->get('/en/admin/jobs/'.$job->slug.'/edit')->assertRedirect();
    }

    /* ── Editing content and taxonomy ───────────────────────────────────── */

    public function test_staff_can_rewrite_the_content_of_a_listing(): void
    {
        $job = $this->job();

        $this->actingAs($this->moderator)
            ->put('/en/admin/jobs/'.$job->slug, $this->jobPayload([
                'title_en' => 'Delivery driver',
                'title_ar' => 'سائق توصيل',
                'excerpt_en' => 'A corrected blurb.',
                'description_en' => "First para.\nSecond para.",
            ]))
            ->assertRedirect();

        $job->refresh();

        $this->assertSame('Delivery driver', $job->title_en);
        $this->assertSame('A corrected blurb.', $job->excerpt_en);
        $this->assertSame(['First para.', 'Second para.'], $job->description_en);
    }

    public function test_staff_can_recategorise_a_listing(): void
    {
        $job = $this->job();

        $otherCategory = Category::where('id', '!=', $job->category_id)->first();
        $otherCity = City::where('id', '!=', $job->city_id)->first();

        $this->actingAs($this->moderator)
            ->put('/en/admin/jobs/'.$job->slug, $this->jobPayload([
                'category_id' => $otherCategory->id,
                'city_id' => $otherCity->id,
            ]))
            ->assertRedirect();

        $job->refresh();

        $this->assertSame($otherCategory->id, $job->category_id);
        $this->assertSame($otherCity->id, $job->city_id);
    }

    public function test_a_staff_edit_leaves_a_live_listing_live(): void
    {
        config(['board.listing.moderated' => true]);

        $job = $this->job();
        $publishedAt = $job->published_at;

        $this->actingAs($this->moderator)
            ->put('/en/admin/jobs/'.$job->slug, $this->jobPayload(['title_en' => 'Corrected title']));

        $job->refresh();

        // The whole point: a fix must not unpublish the thing it fixed.
        $this->assertSame(Job::STATUS_PUBLISHED, $job->status);
        $this->assertTrue($job->isPublished());
        $this->assertEquals($publishedAt->timestamp, $job->published_at->timestamp);
    }

    public function test_an_employers_own_edit_still_goes_back_for_review(): void
    {
        config(['board.listing.moderated' => true]);

        $job = $this->job();

        // The contrast case: the rule staff are exempt from still applies to
        // the person who owns the listing.
        $this->actingAs($this->employer)
            ->put('/en/employer/jobs/'.$job->slug, $this->jobPayload([
                'title_en' => 'Rewritten by the employer',
                'intent' => 'submit',
            ]));

        $job->refresh();

        $this->assertSame(Job::STATUS_PENDING, $job->status);
        $this->assertNull($job->published_at);
    }

    public function test_a_staff_edit_leaves_a_pending_listing_pending(): void
    {
        $job = $this->job(['status' => Job::STATUS_PENDING, 'published_at' => null]);

        $this->actingAs($this->moderator)
            ->put('/en/admin/jobs/'.$job->slug, $this->jobPayload(['title_en' => 'Fixed before approval']));

        $job->refresh();

        $this->assertSame(Job::STATUS_PENDING, $job->status);
        $this->assertSame('Fixed before approval', $job->title_en);
    }

    public function test_validation_still_applies_to_a_staff_edit(): void
    {
        $job = $this->job();

        $this->actingAs($this->moderator)
            ->put('/en/admin/jobs/'.$job->slug, $this->jobPayload(['title_en' => '']))
            ->assertSessionHasErrors('title_en');

        $this->assertSame('Driver', $job->refresh()->title_en);
    }

    /* ── Seeker posts ───────────────────────────────────────────────────── */

    public function test_staff_can_edit_a_seeker_post_without_unpublishing_it(): void
    {
        config(['board.listing.moderated' => true]);

        $post = $this->seekerPost();
        $otherCategory = Category::where('id', '!=', $post->category_id)->first();

        $this->actingAs($this->moderator)
            ->put('/en/admin/seekers/'.$post->slug, [
                'display_name_ar' => 'محمد', 'display_name_en' => 'Mohammed',
                'headline_ar' => 'سائق خاص', 'headline_en' => 'Corrected headline',
                'city_id' => City::first()->id,
                'category_id' => $otherCategory->id,
            ])
            ->assertRedirect();

        $post->refresh();

        $this->assertSame('Corrected headline', $post->headline_en);
        $this->assertSame($otherCategory->id, $post->category_id);
        $this->assertSame(JobSeekerPost::STATUS_PUBLISHED, $post->status);
    }

    public function test_the_review_screens_offer_an_edit_link(): void
    {
        $job = $this->job();
        $post = $this->seekerPost();

        $this->actingAs($this->moderator)
            ->get('/en/admin/jobs/'.$job->slug)
            ->assertOk()
            ->assertSee('/admin/jobs/'.$job->slug.'/edit', false);

        $this->actingAs($this->moderator)
            ->get('/en/admin/seekers/'.$post->slug)
            ->assertOk()
            ->assertSee('/admin/seekers/'.$post->slug.'/edit', false);
    }
}
