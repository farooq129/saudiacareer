<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Database\Seeder;

/**
 * The first admin, so a fresh install can be signed into.
 *
 * Credentials come from the environment and the seeder refuses to invent a
 * password: a default admin password baked into a seeder is the kind of thing
 * that survives all the way to production.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $phone = env('ADMIN_PHONE');
        $password = env('ADMIN_PASSWORD');

        if (blank($phone) || blank($password)) {
            $this->command?->warn(
                'Skipping admin user: set ADMIN_PHONE and ADMIN_PASSWORD in .env first.'
            );

            return;
        }

        $normalised = Phone::normalise($phone);

        if ($normalised === null) {
            $this->command?->error('ADMIN_PHONE is not a valid Saudi mobile number.');

            return;
        }

        $admin = User::updateOrCreate(
            ['phone' => $normalised],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'email' => env('ADMIN_EMAIL'),
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'locale' => 'ar',
                'is_active' => true,
                'phone_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['admin']);

        $this->command?->info("Admin ready: {$normalised}");
    }
}
