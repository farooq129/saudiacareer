<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Job listings — the board itself.
 *
 * Note this is NOT Laravel's queue table; that one is `queue_jobs`, renamed in
 * config/queue.php so this name could be the product's.
 *
 * Every visitor-facing string is stored twice, `_ar` and `_en`, because the
 * board runs in both languages at once: Arabic at `/`, English at `/en`, both
 * indexed. Paragraph lists (description, requirements, benefits) are JSON
 * arrays of strings, matching the shape the design canvas renders.
 *
 * `published_at` and `expires_at` are what the public scope filters on;
 * `status` is what the employer and the moderating admin see. A listing is
 * visible only when status = published AND published_at <= now AND
 * (expires_at is null OR expires_at > now).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // Who posted it. company_id is null for an individual employer who
            // has not registered an organisation (a household hiring a driver).
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('employment_type_id')->constrained()->restrictOnDelete();

            $table->string('title_ar', 200);
            $table->string('title_en', 200);

            // The one-line blurb on the listing card.
            $table->text('excerpt_ar')->nullable();
            $table->text('excerpt_en')->nullable();

            // The body of the ad, as a JSON array of paragraphs.
            //
            // One field, not three: the canvas split it into description,
            // requirements and benefits, but employers here write one block of
            // prose and splitting it only produced two empty boxes on most ads.
            $table->json('description_ar')->nullable();
            $table->json('description_en')->nullable();

            // Salary in SAR. salary_max null means "exactly salary_min";
            // both null means the employer chose not to publish a figure.
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('salary_note_ar')->nullable();
            $table->string('salary_note_en')->nullable();

            // Free text rather than a number of years: the canvas shows
            // "5 سنوات+" / "5+ years", which no integer renders faithfully.
            $table->string('experience_ar', 80)->nullable();
            $table->string('experience_en', 80)->nullable();

            // Who may take the job — iqama transfer, Saudis only, and so on.
            $table->string('eligibility_ar', 120)->nullable();
            $table->string('eligibility_en', 120)->nullable();

            // نقل الكفالة — the single detail that decides most hires here, so
            // it is a filterable flag as well as part of the eligibility text.
            $table->boolean('transfer_available')->default(false)->index();

            // Contact for this vacancy; falls back to the company's when null.
            $table->string('whatsapp', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();

            // Optional card photo. Without one the card falls back to the
            // category's gradient art.
            $table->string('image_path')->nullable();

            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_urgent')->default(false)->index();

            $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'expired', 'filled'])
                ->default('draft');

            // Why an admin rejected it, shown back to the employer.
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('applications_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // The public listing feed: status + window, newest first.
            $table->index(['status', 'published_at']);
            // The three filters on the search bar, in the order they narrow.
            $table->index(['city_id', 'category_id', 'employment_type_id'], 'jobs_filters_index');
            $table->index(['is_featured', 'published_at']);
        });

        // Keyword search over both languages at once. MySQL's default
        // tokeniser splits Arabic on whitespace, which is enough for the job
        // titles and company names this box is used on.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE `jobs` ADD FULLTEXT `jobs_search_fulltext` '.
                '(`title_ar`, `title_en`, `excerpt_ar`, `excerpt_en`)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
