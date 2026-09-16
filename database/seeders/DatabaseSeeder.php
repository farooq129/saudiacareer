<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The default seed.
 *
 * Everything here is safe to run against production: the taxonomies the board
 * cannot work without, the role and permission definitions, and the first
 * admin (which is skipped unless ADMIN_PHONE and ADMIN_PASSWORD are set).
 *
 * The sample listings from the design canvas are deliberately NOT included —
 * run them explicitly when you want a board to look at:
 *
 *     php artisan db:seed --class=CanvasContentSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            CategorySeeder::class,
            EmploymentTypeSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
