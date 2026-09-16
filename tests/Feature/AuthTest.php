<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\EmploymentType;
use App\Models\Job;
use App\Models\User;
use App\Services\SavedJobService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\EmploymentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CitySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(EmploymentTypeSeeder::class);
    }

    public function test_a_seeker_can_register_with_a_locally_written_phone_number(): void
    {
        $this->post('/register', [
            'name' => 'أحمد القحطاني',
            'phone' => '0551234567',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => User::ROLE_SEEKER,
        ])->assertRedirect();

        $this->assertAuthenticated();

        // Stored in one canonical form, whatever form it was typed in.
        $this->assertDatabaseHas('users', [
            'phone' => '+966551234567',
            'role' => User::ROLE_SEEKER,
        ]);
    }

    public function test_registering_in_english_stores_english_as_the_preference(): void
    {
        $this->post('/en/register', [
            'name' => 'Sara Al-Otaibi',
            'phone' => '0559876543',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => User::ROLE_EMPLOYER,
        ])->assertRedirect();

        $this->assertSame('en', User::where('phone', '+966559876543')->value('locale'));
    }

    public function test_the_same_number_written_two_ways_cannot_make_two_accounts(): void
    {
        User::create([
            'name' => 'Existing',
            'phone' => '+966551234567',
            'password' => 'secret-password',
            'role' => User::ROLE_SEEKER,
        ]);

        $this->post('/register', [
            'name' => 'Duplicate',
            'phone' => '966 55 123 4567',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => User::ROLE_SEEKER,
        ])->assertSessionHasErrors('phone');

        $this->assertSame(1, User::count());
    }

    public function test_a_user_can_sign_in_with_any_written_form_of_their_number(): void
    {
        User::create([
            'name' => 'Ahmed',
            'phone' => '+966551234567',
            'password' => 'secret-password',
            'role' => User::ROLE_SEEKER,
        ]);

        $this->post('/login', [
            'phone' => '0551234567',
            'password' => 'secret-password',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_an_inactive_account_cannot_sign_in(): void
    {
        $user = User::create([
            'name' => 'Suspended',
            'phone' => '+966551234567',
            'password' => 'secret-password',
            'role' => User::ROLE_SEEKER,
        ]);

        $user->forceFill(['is_active' => false])->save();

        $this->post('/login', [
            'phone' => '0551234567',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_ads_saved_before_signing_in_follow_the_visitor_into_their_account(): void
    {
        $job = $this->publishedJob();

        // Saved while signed out — the list lives in the session.
        $this->post('/saved/'.$job->slug)->assertRedirect();
        $this->assertCount(1, session(SavedJobService::SESSION_KEY));

        User::create([
            'name' => 'Ahmed',
            'phone' => '+966551234567',
            'password' => 'secret-password',
            'role' => User::ROLE_SEEKER,
        ]);

        $this->post('/login', [
            'phone' => '0551234567',
            'password' => 'secret-password',
        ])->assertRedirect();

        $this->assertDatabaseHas('saved_jobs', ['job_id' => $job->id]);
        $this->assertEmpty(session(SavedJobService::SESSION_KEY, []));
    }

    private function publishedJob(): Job
    {
        $employer = User::create([
            'name' => 'Employer',
            'phone' => '+966500000001',
            'password' => 'secret-password',
            'role' => User::ROLE_EMPLOYER,
        ]);

        $company = Company::create([
            'user_id' => $employer->id,
            'slug' => 'test-co',
            'name_ar' => 'شركة الاختبار',
            'name_en' => 'Test Co.',
        ]);

        return Job::create([
            'user_id' => $employer->id,
            'company_id' => $company->id,
            'city_id' => City::where('key', 'riyadh')->value('id'),
            'category_id' => Category::where('key', 'drivers')->value('id'),
            'employment_type_id' => EmploymentType::where('key', 'full')->value('id'),
            'slug' => 'test-driver',
            'title_ar' => 'سائق',
            'title_en' => 'Driver',
            'status' => Job::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);
    }
}
