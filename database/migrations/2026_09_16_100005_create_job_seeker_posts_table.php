<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "إعلانات الباحثين عن عمل" — people advertising themselves rather than a
 * vacancy. The other half of the board, and the half employers browse.
 *
 * Deliberately the same shape as a listing (city, category, headline, blurb,
 * posted-at, status window) so one card component renders both feeds.
 *
 * `display_name_*` is stored on the post rather than read from the user: a
 * seeker may post under a shortened or transliterated name, and the two
 * languages need different spellings of it for the card monogram to read well.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_seeker_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained()->nullOnDelete();

            $table->string('display_name_ar', 160);
            $table->string('display_name_en', 160);

            // The headline: "سائق شاحنة ثقيل — رخصة ثقيل سارية".
            $table->string('headline_ar', 200);
            $table->string('headline_en', 200);

            // The two-line self-pitch under the headline.
            $table->text('pitch_ar')->nullable();
            $table->text('pitch_en')->nullable();

            $table->string('experience_ar', 80)->nullable();
            $table->string('experience_en', 80)->nullable();

            // Salary the seeker is asking for, in SAR. Usually left empty.
            $table->unsignedInteger('expected_salary_min')->nullable();
            $table->unsignedInteger('expected_salary_max')->nullable();

            // نقل الكفالة متاح — earns a tag of its own on the card.
            $table->boolean('transfer_available')->default(false)->index();
            $table->boolean('available_immediately')->default(false);

            $table->string('cv_path')->nullable();

            $table->string('whatsapp', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();

            $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'expired', 'hired'])
                ->default('draft');

            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['city_id', 'category_id'], 'seeker_posts_filters_index');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE `job_seeker_posts` ADD FULLTEXT `seeker_posts_search_fulltext` '.
                '(`headline_ar`, `headline_en`, `pitch_ar`, `pitch_en`)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_seeker_posts');
    }
};
