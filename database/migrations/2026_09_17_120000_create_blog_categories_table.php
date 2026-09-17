<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog sections — "Career advice", "Labour law", and so on.
 *
 * Deliberately a thin taxonomy rather than free tags: the board's other
 * taxonomies (cities, job categories) are closed lists an admin curates, and a
 * section that appears in a crawlable URL should not multiply on a typo.
 *
 * `key` is the language-neutral slug that appears in /blog/section/{key}, so a
 * shared link survives a language switch exactly like a job category does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();

            $table->string('name_ar', 120);
            $table->string('name_en', 120);

            // Shown on the section's own page and in its meta description.
            $table->string('blurb_ar')->nullable();
            $table->string('blurb_en')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_categories');
    }
};
