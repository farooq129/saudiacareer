<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The roles and the permissions inside them.
 *
 * `users.role` says which area of the product an account belongs to; these
 * permissions say what it may do once inside. The split does real work for the
 * two staff roles: an admin and a moderator both live in the admin area, and
 * the only thing separating them is this list.
 *
 * What a moderator deliberately does NOT get:
 *
 *   users.manage        — cannot suspend accounts or create other staff
 *   companies.verify    — cannot grant the verified badge, which is the one
 *                         signal a seeker has that an employer was checked
 *   taxonomy.manage     — cannot reshape the cities and categories the whole
 *                         board is indexed on
 *
 * All three are decisions that are hard to undo or easy to abuse quietly.
 * Approving an ad, by contrast, is visible, reversible, and the job.
 */
class RoleSeeder extends Seeder
{
    /** Permission name => which roles hold it. */
    private const PERMISSIONS = [
        // Moderation — the moderator's whole remit.
        'jobs.moderate' => ['admin', 'moderator'],
        'jobs.publish.any' => ['admin', 'moderator'],
        'reports.review' => ['admin', 'moderator'],
        'companies.view' => ['admin', 'moderator'],

        // Admin only.
        'companies.verify' => ['admin'],
        'users.manage' => ['admin'],
        'staff.manage' => ['admin'],
        'taxonomy.manage' => ['admin'],

        // An employer's own listings.
        'jobs.create' => ['admin', 'employer'],
        'jobs.update.own' => ['admin', 'employer'],
        'jobs.delete.own' => ['admin', 'employer'],
        'applications.review.own' => ['admin', 'employer'],
        'company.update.own' => ['admin', 'employer'],

        // A seeker's own posts and applications.
        'seeker_posts.create' => ['admin', 'seeker'],
        'seeker_posts.update.own' => ['admin', 'seeker'],
        'applications.create' => ['admin', 'seeker'],
        'jobs.save' => ['admin', 'seeker'],

        // Everyone signed in can change their own password.
        'account.update.own' => ['admin', 'moderator', 'employer', 'seeker'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [];

        foreach (User::ROLES as $name) {
            $roles[$name] = Role::findOrCreate($name, 'web');
        }

        foreach (self::PERMISSIONS as $name => $holders) {
            $permission = Permission::findOrCreate($name, 'web');

            foreach ($holders as $role) {
                $roles[$role]->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
