<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job categories — the tiles on the home page and the primary browse axis.
 *
 * Each category carries its own artwork: `svg_path` is the drawn 24x24 icon in
 * public/icons/categories, `icon` is the Phosphor name kept as a fallback if an
 * SVG is ever removed, and the three `art_*` colours are the card gradient the
 * canvas uses when a listing has no photo of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();

            $table->string('name_ar', 120);
            $table->string('name_en', 120);

            $table->string('icon', 60)->nullable();
            $table->string('svg_path')->nullable();

            // Card fallback art: light stop, dark stop, and the ink colour used
            // for the glyph drawn over them. Hex, including the leading '#'.
            $table->string('art_from', 9)->nullable();
            $table->string('art_to', 9)->nullable();
            $table->string('art_ink', 9)->nullable();

            $table->unsignedInteger('jobs_count')->default(0);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
