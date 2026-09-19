<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\EmploymentType;
use App\Models\JobSeekerPost;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\EmploymentTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fields withdrawn from seeker posts for now: expected pay, and employment type.
 *
 * Nothing is dropped — the columns stay on job_seeker_posts, the relations and
 * the validation rules stay put, and each field is behind a single flag in the
 * form. So the point of these tests is the part that is easy to get wrong when
 * a control disappears: a value already stored must survive an edit made
 * through the form that no longer carries it. Eloquent's fill() only touches
 * keys present in the payload, and that is asserted here rather than assumed.
 */
class SeekerPostShelvedFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $seeker;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CitySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(EmploymentTypeSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->seeker = User::create([
            'name' => 'Seeker', 'phone' => '+966550000301',
            'password' => 'secret-password', 'role' => User::ROLE_SEEKER,
        ]);

        $this->moderator = User::create([
            'name' => 'Moderator', 'phone' => '+966550000302',
            'password' => 'secret-password', 'role' => User::ROLE_MODERATOR,
        ]);
    }

    private function seekerPost(array $overrides = []): JobSeekerPost
    {
        return JobSeekerPost::create(array_merge([
            'user_id' => $this->seeker->id,
            'city_id' => City::first()->id,
            'category_id' => Category::first()->id,
            'slug' => 'a-seeker',
            'display_name_ar' => 'محمد', 'display_name_en' => 'Mohammed',
            'headline_ar' => 'سائق خاص', 'headline_en' => 'Private driver',
            'status' => JobSeekerPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'display_name_ar' => 'محمد', 'display_name_en' => 'Mohammed',
            'headline_ar' => 'سائق خاص', 'headline_en' => 'Private driver',
            'city_id' => City::first()->id,
            'category_id' => Category::first()->id,
        ], $overrides);
    }

    public function test_the_seeker_form_does_not_offer_expected_pay(): void
    {
        $this->actingAs($this->seeker)
            ->get('/en/seeker/posts/new')
            ->assertOk()
            ->assertDontSee('name="expected_salary_min"', false)
            ->assertDontSee('name="expected_salary_max"', false)
            ->assertDontSee('Pay you are looking for');
    }

    public function test_the_seeker_edit_form_does_not_offer_expected_pay(): void
    {
        $post = $this->seekerPost(['expected_salary_min' => 4000]);

        $this->actingAs($this->seeker)
            ->get('/en/seeker/posts/'.$post->slug.'/edit')
            ->assertOk()
            ->assertDontSee('name="expected_salary_min"', false);
    }

    public function test_the_remaining_block_still_shows_its_two_checkboxes(): void
    {
        // Those checkboxes used to sit under the pay heading; losing the
        // heading must not lose them.
        $this->actingAs($this->seeker)
            ->get('/en/seeker/posts/new')
            ->assertOk()
            ->assertSee('name="transfer_available"', false)
            ->assertSee('name="available_immediately"', false)
            ->assertSee('Availability');
    }

    public function test_a_stored_figure_survives_an_edit_through_the_hidden_form(): void
    {
        $post = $this->seekerPost([
            'expected_salary_min' => 4200,
            'expected_salary_max' => 5500,
        ]);

        $this->actingAs($this->seeker)
            ->put('/en/seeker/posts/'.$post->slug, $this->payload([
                'headline_en' => 'Updated headline',
                'intent' => 'submit',
            ]));

        $post->refresh();

        $this->assertSame('Updated headline', $post->headline_en);
        // The figures must not be blanked by a form that no longer carries them.
        $this->assertSame(4200, $post->expected_salary_min);
        $this->assertSame(5500, $post->expected_salary_max);
    }

    public function test_the_staff_edit_screen_does_not_offer_expected_pay_either(): void
    {
        $post = $this->seekerPost(['expected_salary_min' => 4200]);

        $this->actingAs($this->moderator)
            ->get('/en/admin/seekers/'.$post->slug.'/edit')
            ->assertOk()
            ->assertDontSee('name="expected_salary_min"', false)
            ->assertDontSee('Pay you are looking for');
    }

    public function test_a_stored_figure_survives_a_staff_edit_too(): void
    {
        $post = $this->seekerPost([
            'expected_salary_min' => 4200,
            'expected_salary_max' => 5500,
        ]);

        $this->actingAs($this->moderator)
            ->put('/en/admin/seekers/'.$post->slug, $this->payload([
                'headline_en' => 'Corrected by staff',
            ]));

        $post->refresh();

        $this->assertSame('Corrected by staff', $post->headline_en);
        $this->assertSame(4200, $post->expected_salary_min);
        $this->assertSame(5500, $post->expected_salary_max);
    }

    /* ── Employment type, shelved on the same terms ─────────────────── */

    public function test_neither_form_offers_employment_type(): void
    {
        $post = $this->seekerPost(['employment_type_id' => EmploymentType::first()->id]);

        $this->actingAs($this->seeker)
            ->get('/en/seeker/posts/new')
            ->assertOk()
            ->assertDontSee('name="employment_type_id"', false);

        $this->actingAs($this->seeker)
            ->get('/en/seeker/posts/'.$post->slug.'/edit')
            ->assertOk()
            ->assertDontSee('name="employment_type_id"', false);

        $this->actingAs($this->moderator)
            ->get('/en/admin/seekers/'.$post->slug.'/edit')
            ->assertOk()
            ->assertDontSee('name="employment_type_id"', false);
    }

    public function test_the_public_post_no_longer_shows_employment_type(): void
    {
        $type = EmploymentType::first();
        $post = $this->seekerPost(['employment_type_id' => $type->id]);

        $this->get('/en/seekers/'.$post->slug)
            ->assertOk()
            ->assertDontSee($type->name_en);
    }

    public function test_a_stored_employment_type_survives_an_edit(): void
    {
        $type = EmploymentType::first();
        $post = $this->seekerPost(['employment_type_id' => $type->id]);

        $this->actingAs($this->seeker)
            ->put('/en/seeker/posts/'.$post->slug, $this->payload([
                'headline_en' => 'Updated headline',
                'intent' => 'submit',
            ]));

        $this->assertSame($type->id, $post->refresh()->employment_type_id);
    }

    public function test_the_columns_are_still_on_the_table(): void
    {
        // The brief was to hide the option, not to drop the data.
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumns('job_seeker_posts', [
                'expected_salary_min', 'expected_salary_max', 'employment_type_id',
            ])
        );
    }
}
