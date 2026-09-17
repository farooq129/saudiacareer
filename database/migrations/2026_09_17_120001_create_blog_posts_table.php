<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blog articles.
 *
 * Shaped like the jobs table on purpose — every visitor-facing string stored
 * twice, `_ar` and `_en`, so MySQL can index and search each language on its
 * own, and the body held as a JSON array of paragraphs rather than an HTML
 * blob.
 *
 * That last choice is the important one. An admin-authored HTML body would mean
 * either trusting staff markup verbatim or shipping a sanitiser; storing
 * paragraphs means the view escapes every one of them and there is no path from
 * the editor to raw markup on a public page.
 *
 * The meta_* columns exist because a good <title> and a good on-page headline
 * are different sentences: the headline sells the click to a reader already on
 * the site, the title tag sells it in a result list next to nine competitors.
 * Both fall back to the headline when an editor leaves them blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();

            // The section. Nullable so a post can be written before the
            // taxonomy question is settled, and nulled rather than cascaded if
            // a section is ever retired.
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();

            // The staff member who wrote it. Kept on the row after the account
            // goes, so an article never loses its byline mid-life.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();

            $table->string('title_ar', 200);
            $table->string('title_en', 200);

            // The card blurb, and the default meta description.
            $table->text('excerpt_ar')->nullable();
            $table->text('excerpt_en')->nullable();

            // The article, as a JSON array of paragraphs. See the note above.
            $table->json('body_ar')->nullable();
            $table->json('body_en')->nullable();

            // One image per post, as the brief asked. The alt text is stored
            // per language because it is real prose a screen reader reads out,
            // not a filename.
            $table->string('image_path')->nullable();
            $table->string('image_alt_ar')->nullable();
            $table->string('image_alt_en')->nullable();

            /* ── SEO ────────────────────────────────────────────────────── */
            $table->string('meta_title_ar', 180)->nullable();
            $table->string('meta_title_en', 180)->nullable();
            $table->string('meta_description_ar', 320)->nullable();
            $table->string('meta_description_en', 320)->nullable();

            // Lets an editor keep a thin or duplicated page off the index
            // without unpublishing it — a thank-you page, a seasonal repost.
            $table->boolean('is_indexable')->default(true)->index();

            // Where the canonical points when this article is a republished
            // version of something that lives elsewhere. Null means itself.
            $table->string('canonical_url')->nullable();

            $table->boolean('is_featured')->default(false)->index();

            $table->enum('status', ['draft', 'published'])->default('draft');

            // published_at is the public gate AND the dateline. A future value
            // schedules the post; the published scope will not show it early.
            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // The public feed: live posts, newest first.
            $table->index(['status', 'published_at']);
            // A section's own page, same ordering.
            $table->index(['blog_category_id', 'status', 'published_at'], 'blog_posts_section_index');
            $table->index(['is_featured', 'published_at']);
        });

        // Keyword search across both languages at once, matching the jobs table.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE `blog_posts` ADD FULLTEXT `blog_posts_search_fulltext` '.
                '(`title_ar`, `title_en`, `excerpt_ar`, `excerpt_en`)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
