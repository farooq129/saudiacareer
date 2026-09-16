<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\EmploymentTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Staff accounts: who may work here, what a moderator may reach, and the
 * guards that stop the board being left with nobody able to administer it.
 */
class StaffTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CitySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(EmploymentTypeSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->admin = $this->staff('+966550000001', User::ROLE_ADMIN);
        $this->moderator = $this->staff('+966550000002', User::ROLE_MODERATOR);
    }

    public function test_a_moderator_reaches_the_queues_but_not_accounts_or_staff(): void
    {
        $this->actingAs($this->moderator)->get('/admin')->assertOk();
        $this->actingAs($this->moderator)->get('/admin/jobs')->assertOk();
        $this->actingAs($this->moderator)->get('/admin/seekers')->assertOk();
        $this->actingAs($this->moderator)->get('/admin/reports')->assertOk();
        // They can read the employer directory — reviewing a listing means
        // knowing who is behind it.
        $this->actingAs($this->moderator)->get('/admin/companies')->assertOk();
        // And their own account, so they can change their own password.
        $this->actingAs($this->moderator)->get('/admin/account')->assertOk();

        $this->actingAs($this->moderator)->get('/admin/users')->assertRedirect();
        $this->actingAs($this->moderator)->get('/admin/staff')->assertRedirect();
    }

    public function test_a_moderator_cannot_grant_the_verified_badge(): void
    {
        $company = Company::create([
            'user_id' => $this->admin->id,
            'slug' => 'test-co',
            'name_ar' => 'شركة',
            'name_en' => 'Test Co.',
        ]);

        $this->actingAs($this->moderator)
            ->patch('/admin/companies/'.$company->slug.'/verify')
            ->assertRedirect();

        $this->assertFalse($company->fresh()->is_verified);
    }

    public function test_an_admin_can_add_a_moderator(): void
    {
        $this->actingAs($this->admin)->post('/admin/staff', [
            'name' => 'New Moderator',
            'phone' => '0553330009',
            'role' => User::ROLE_MODERATOR,
            'password' => 'a-long-enough-password',
            'password_confirmation' => 'a-long-enough-password',
        ])->assertRedirect();

        $created = User::firstWhere('phone', '+966553330009');

        $this->assertNotNull($created);
        $this->assertSame(User::ROLE_MODERATOR, $created->role);
        $this->assertTrue($created->hasRole(User::ROLE_MODERATOR));
        // Created by someone who has already checked who they are.
        $this->assertNotNull($created->phone_verified_at);

        // And the account works. Sign the admin out first — the login form is
        // behind the `guest` middleware, so posting to it while still
        // authenticated is bounced, not processed.
        auth()->logout();

        $this->post('/login', ['phone' => '0553330009', 'password' => 'a-long-enough-password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($created);
    }

    public function test_a_staff_password_must_be_long(): void
    {
        $this->actingAs($this->admin)->post('/admin/staff', [
            'name' => 'Too Easy',
            'phone' => '0553330010',
            'role' => User::ROLE_MODERATOR,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertNull(User::firstWhere('phone', '+966553330010'));
    }

    public function test_a_moderator_cannot_add_staff(): void
    {
        $this->actingAs($this->moderator)->post('/admin/staff', [
            'name' => 'Sneaky',
            'phone' => '0553330011',
            'role' => User::ROLE_ADMIN,
            'password' => 'a-long-enough-password',
            'password_confirmation' => 'a-long-enough-password',
        ])->assertRedirect();

        $this->assertNull(User::firstWhere('phone', '+966553330011'));
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        $this->actingAs($this->admin)
            ->patch('/admin/staff/'.$this->admin->id.'/role', ['role' => User::ROLE_MODERATOR])
            ->assertSessionHasErrors('access');

        $this->assertSame(User::ROLE_ADMIN, $this->admin->fresh()->role);
    }

    public function test_the_last_admin_cannot_be_demoted_or_removed(): void
    {
        // A second admin does the demoting, so cannotChangeSelf is not what is
        // being tested here.
        $other = $this->staff('+966550000003', User::ROLE_ADMIN);

        // Two admins: demoting one is fine.
        $this->actingAs($other)
            ->patch('/admin/staff/'.$this->admin->id.'/role', ['role' => User::ROLE_MODERATOR])
            ->assertRedirect();

        $this->assertSame(User::ROLE_MODERATOR, $this->admin->fresh()->role);
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());

        // One admin left: promote a third so someone else can try to take the
        // last one down, then demote them back so only `$other` remains.
        $third = $this->staff('+966550000004', User::ROLE_ADMIN);
        $this->actingAs($third)
            ->patch('/admin/staff/'.$third->id.'/role', ['role' => User::ROLE_MODERATOR])
            ->assertSessionHasErrors('access');

        // Now genuinely down to one, and it cannot be removed.
        $third->forceFill(['role' => User::ROLE_MODERATOR])->save();
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());

        $this->actingAs($third)
            ->delete('/admin/staff/'.$other->id)
            ->assertSessionHasErrors('access');

        $this->assertNotSoftDeleted('users', ['id' => $other->id]);
    }

    public function test_changing_your_own_password_needs_the_current_one(): void
    {
        $this->actingAs($this->moderator)->put('/admin/account/password', [
            'current_password' => 'not-the-password',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertFalse(Hash::check('a-brand-new-password', $this->moderator->fresh()->password));

        $this->actingAs($this->moderator)->put('/admin/account/password', [
            'current_password' => 'secret-password',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('a-brand-new-password', $this->moderator->fresh()->password));
    }

    public function test_an_admin_can_set_a_colleagues_password_without_knowing_the_old_one(): void
    {
        // The point of this action is that the colleague has lost theirs.
        $this->actingAs($this->admin)
            ->patch('/admin/staff/'.$this->moderator->id.'/password', [
                'password' => 'reset-by-the-admin',
                'password_confirmation' => 'reset-by-the-admin',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('reset-by-the-admin', $this->moderator->fresh()->password));
    }

    public function test_staff_management_only_touches_staff(): void
    {
        $seeker = User::create([
            'name' => 'Seeker', 'phone' => '+966559990001',
            'password' => 'secret-password', 'role' => User::ROLE_SEEKER,
        ]);

        $this->actingAs($this->admin)
            ->patch('/admin/staff/'.$seeker->id.'/role', ['role' => User::ROLE_ADMIN])
            ->assertNotFound();

        $this->assertSame(User::ROLE_SEEKER, $seeker->fresh()->role);
    }

    private function staff(string $phone, string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'phone' => $phone,
            'password' => 'secret-password',
            'role' => $role,
        ]);

        $user->syncRoles([$role]);

        return $user;
    }
}
