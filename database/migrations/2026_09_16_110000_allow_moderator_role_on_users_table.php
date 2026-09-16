<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the moderator role.
 *
 * A moderator works the review queues but cannot touch accounts, grant the
 * verified badge, or create other staff. They live in the admin area, so
 * `role` is the right column for them — it is the coarse "which part of the
 * product is this account for" split, and a moderator's answer is "the admin
 * part".
 *
 * The column becomes a plain string rather than gaining a fourth enum value.
 * An enum has to be ALTERed on MySQL and rebuilt wholesale on SQLite every time
 * the list changes, which is a migration nobody enjoys writing twice; the
 * allowed values are already declared as constants on the User model and
 * enforced by the validation rules that create accounts, so the database
 * constraint was buying very little.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No ->index() here: the index came with the original users migration
        // and survives a type change. Restating it asks MySQL to create a
        // duplicate key and the migration dies half-applied.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('seeker')->change();
        });
    }

    public function down(): void
    {
        // Any moderator would violate the enum on the way back down, so they
        // are demoted to the nearest thing that still exists.
        User::query()->where('role', 'moderator')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'employer', 'seeker'])->default('seeker')->change();
        });
    }
};
