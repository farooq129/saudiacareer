<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "الإعلانات المحفوظة / Saved ads" list in the header.
 *
 * A guest's saved ads live in their session until they sign in, at which point
 * the session list is merged in here — hence the plain unique pair rather than
 * anything clever: merging must be idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'job_id']);
            // The saved list itself: one user's ads, most recently saved first.
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_jobs');
    }
};
