<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saudi cities a listing can be placed in.
 *
 * `key` is the stable slug the design canvas filters on ("riyadh", "jeddah").
 * Filters compare keys, never display names, so switching the interface
 * language never drops the visitor's current filter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();

            $table->string('name_ar', 120);
            $table->string('name_en', 120);

            // Administrative region, for the "jobs by region" rollup.
            $table->string('region_ar', 120)->nullable();
            $table->string('region_en', 120)->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Denormalised count of published listings, refreshed by the board
            // rather than counted on every home-page render.
            $table->unsignedInteger('jobs_count')->default(0);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
