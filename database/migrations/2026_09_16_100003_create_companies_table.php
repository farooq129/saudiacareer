<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employers. One row per hiring organisation, owned by one employer user.
 *
 * `is_verified` is the "صاحب عمل موثّق / Verified employer" badge the detail
 * page shows — set by an admin after checking the commercial registration, not
 * by the employer themselves, which is why the reviewing admin is recorded.
 *
 * The contact triple (whatsapp / phone / email) lives here as the employer's
 * default; a listing may override any of them for a specific vacancy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('slug')->unique();
            $table->string('name_ar', 180);
            $table->string('name_en', 180);

            $table->string('logo_path')->nullable();

            // The two-line "about the employer" paragraph under a listing.
            $table->text('blurb_ar')->nullable();
            $table->text('blurb_en')->nullable();

            $table->string('website')->nullable();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();

            // Commercial registration (السجل التجاري) — the document an admin
            // checks before granting the verified badge.
            $table->string('cr_number', 40)->nullable();
            $table->unsignedSmallInteger('staff_count')->nullable();

            // Year the employer joined, shown as "عضو منذ / Member since".
            $table->year('member_since')->nullable();

            $table->string('whatsapp', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();

            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('jobs_count')->default(0);

            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
