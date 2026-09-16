<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full-time, contract, part-time, remote — the third filter on the search bar.
 *
 * A table rather than an enum so an admin can add a type (internship, seasonal)
 * without a deployment, and so the Arabic and English labels live beside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();

            $table->string('name_ar', 80);
            $table->string('name_en', 80);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_types');
    }
};
