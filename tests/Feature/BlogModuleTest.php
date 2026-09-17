<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The blog, from both sides.
 *
 * The visibility rules get the most attention here: a draft or a scheduled
 * article reaching the public is the expensive mistake in this module, and the
 * structured-data assertion guards a bug that is invisible in a browser —
 * `@context` is a Blade directive, so written inline in a template it compiles
 * to PHP source and ships a script block no crawler can read.
 */
class BlogModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /** Phone is the board's login identifier and is NOT NULL, so it is given
     *  explicitly — the factory does not set one. */
    private function staff(string $role, string $phone): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'phone' => $phone,
            'password' => 'secret-password',
            'role' => $role,
        ]);
    }

    private function admin(): User
    {
        return $this->staff(User::ROLE_ADMIN, '+966550000101');
    }

    private function section(): BlogCategory
    {
        return BlogCategory::create([
            'key' => 'career-advice',
            'name_ar' => 'نصائح مهنية',
            'name_en' => 'Career advice',
            'is_active' => true,
        ]);
    }

    private function livePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'slug' => 'live-one',
            'title_ar' => 'منشور',
            'title_en' => 'Live one',
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /* ── What the public may see ────────────────────────────────────────── */

    public function test_the_feed_shows_only_published_posts(): void
    {
        $this->livePost();

        BlogPost::create([
            'slug' => 'draft-one', 'title_ar' => 'مسودة', 'title_en' => 'Draft one',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        BlogPost::create([
            'slug' => 'future-one', 'title_ar' => 'مجدول', 'title_en' => 'Scheduled one',
            'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => now()->addWeek(),
        ]);

        $this->get('/en/blog')
            ->assertOk()
            ->assertSee('Live one')
            ->assertDontSee('Draft one')
            ->assertDontSee('Scheduled one');
    }

    public function test_drafts_and_scheduled_posts_are_not_reachable_by_url(): void
    {
        BlogPost::create([
            'slug' => 'draft-one', 'title_ar' => 'مسودة', 'title_en' => 'Draft one',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        BlogPost::create([
            'slug' => 'future-one', 'title_ar' => 'مجدول', 'title_en' => 'Scheduled one',
            'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => now()->addWeek(),
        ]);

        $this->get('/en/blog/draft-one')->assertNotFound();
        $this->get('/en/blog/future-one')->assertNotFound();
    }

    public function test_the_blog_is_published_in_both_languages(): void
    {
        $this->livePost();

        $this->get('/blog')->assertOk();
        $this->get('/en/blog')->assertOk();
        $this->get('/blog/live-one')->assertOk();
        $this->get('/en/blog/live-one')->assertOk();
    }

    public function test_a_section_page_lists_only_its_own_articles(): void
    {
        $section = $this->section();

        $this->livePost(['blog_category_id' => $section->id]);
        $this->livePost(['slug' => 'other-one', 'title_en' => 'Other one', 'title_ar' => 'آخر']);

        $this->get('/en/blog/section/career-advice')
            ->assertOk()
            ->assertSee('Live one')
            ->assertDontSee('Other one');
    }

    /* ── SEO ────────────────────────────────────────────────────────────── */

    public function test_the_article_publishes_parseable_structured_data(): void
    {
        $section = $this->section();

        $this->livePost([
            'blog_category_id' => $section->id,
            'excerpt_en' => 'A blurb.',
            'body_en' => ['One paragraph.', 'Another one.'],
        ]);

        $html = $this->get('/en/blog/live-one')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $this->assertCount(2, $matches[1], 'Expected a BlogPosting and a BreadcrumbList.');

        $types = [];

        foreach ($matches[1] as $json) {
            $data = json_decode(trim($json), true);

            $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Every JSON-LD block must parse.');
            $this->assertSame('https://schema.org', $data['@context'] ?? null);

            $types[] = $data['@type'];
        }

        $this->assertEqualsCanonicalizing(['BlogPosting', 'BreadcrumbList'], $types);
    }

    public function test_the_article_declares_exactly_one_canonical(): void
    {
        $this->livePost();

        $html = $this->get('/en/blog/live-one')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
    }

    public function test_an_editors_canonical_overrides_the_default(): void
    {
        $this->livePost(['canonical_url' => 'https://example.com/original']);

        $html = $this->get('/en/blog/live-one')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
        $this->assertStringContainsString('href="https://example.com/original"', $html);
    }

    public function test_meta_title_and_description_fall_back_to_the_headline(): void
    {
        $post = $this->livePost(['excerpt_en' => 'The standfirst.']);

        $this->assertSame('Live one', $post->metaTitle('en'));
        $this->assertSame('The standfirst.', $post->metaDescription('en'));

        $post->meta_title_en = 'A better title tag';
        $post->meta_description_en = 'A better description.';

        $this->assertSame('A better title tag', $post->metaTitle('en'));
        $this->assertSame('A better description.', $post->metaDescription('en'));
    }

    public function test_a_noindex_post_is_marked_and_left_out_of_the_sitemap(): void
    {
        $this->livePost(['slug' => 'hidden-one', 'is_indexable' => false]);

        $this->get('/en/blog/hidden-one')
            ->assertOk()
            ->assertSee('noindex, follow', false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('hidden-one');
    }

    public function test_the_sitemap_is_well_formed_and_lists_the_blog(): void
    {
        $this->livePost();

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        libxml_use_internal_errors(true);

        $this->assertNotFalse(simplexml_load_string($xml), 'The sitemap must be valid XML.');
        $this->assertStringContainsString('blog/live-one', $xml);
    }

    public function test_robots_points_at_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap:', false)
            ->assertSee('Disallow: /admin', false);
    }

    /* ── The editor's side ──────────────────────────────────────────────── */

    public function test_a_guest_cannot_reach_the_admin_blog(): void
    {
        $this->get('/en/admin/blog')->assertRedirect();
    }

    public function test_a_seeker_cannot_reach_the_admin_blog(): void
    {
        $seeker = $this->staff(User::ROLE_SEEKER, '+966550000102');

        // EnsureRole sends someone to their own area with an error rather than
        // showing a 403 — a seeker who lands here took a wrong turn, they are
        // not an intruder to be stonewalled.
        $this->actingAs($seeker)
            ->get('/en/admin/blog')
            ->assertRedirect()
            ->assertSessionHasErrors('access');
    }

    public function test_an_admin_can_write_a_post_with_an_image(): void
    {
        Storage::fake('public');

        $section = $this->section();

        $this->actingAs($this->admin())
            ->post('/en/admin/blog', [
                'title_ar' => 'عنوان جديد',
                'title_en' => 'A new article',
                'blog_category_id' => $section->id,
                'body_en' => "First para.\n\n   \nSecond para.",
                'image' => UploadedFile::fake()->image('hero.jpg', 1200, 630),
                'status' => BlogPost::STATUS_PUBLISHED,
                'is_indexable' => '1',
            ])
            ->assertRedirect();

        $post = BlogPost::firstWhere('title_en', 'A new article');

        $this->assertNotNull($post);
        $this->assertSame('a-new-article', $post->slug);
        // Blank and whitespace-only lines are dropped on the way in.
        $this->assertSame(['First para.', 'Second para.'], $post->body_en);
        $this->assertNotNull($post->published_at);
        $this->assertNotNull($post->image_path);

        Storage::disk('public')->assertExists(str_replace('storage/', '', $post->image_path));
    }

    public function test_replacing_an_image_removes_the_old_file(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        $this->actingAs($admin)->post('/en/admin/blog', [
            'title_ar' => 'عنوان', 'title_en' => 'With image',
            'image' => UploadedFile::fake()->image('first.jpg'),
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $post = BlogPost::firstWhere('title_en', 'With image');
        $first = str_replace('storage/', '', $post->image_path);

        $this->actingAs($admin)->put('/en/admin/blog/'.$post->slug, [
            'title_ar' => 'عنوان', 'title_en' => 'With image',
            'image' => UploadedFile::fake()->image('second.jpg'),
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $second = str_replace('storage/', '', $post->refresh()->image_path);

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_an_svg_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/en/admin/blog', [
                'title_ar' => 'عنوان', 'title_en' => 'Script carrier',
                'image' => UploadedFile::fake()->create('payload.svg', 8, 'image/svg+xml'),
                'status' => BlogPost::STATUS_DRAFT,
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_the_slug_survives_a_retitle(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/en/admin/blog', [
            'title_ar' => 'عنوان', 'title_en' => 'Original headline',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $post = BlogPost::firstWhere('title_en', 'Original headline');
        $this->assertSame('original-headline', $post->slug);

        $this->actingAs($admin)->put('/en/admin/blog/'.$post->slug, [
            'title_ar' => 'عنوان', 'title_en' => 'Rewritten headline',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        // A URL already shared must not move because the headline changed.
        $this->assertSame('original-headline', $post->refresh()->slug);
    }

    public function test_publish_and_unpublish_toggle_public_visibility(): void
    {
        $admin = $this->admin();

        $post = BlogPost::create([
            'slug' => 'toggle-me', 'title_ar' => 'مسودة', 'title_en' => 'Toggle me',
            'status' => BlogPost::STATUS_DRAFT,
        ]);

        $this->actingAs($admin)->patch('/en/admin/blog/toggle-me/publish');
        $this->assertTrue($post->refresh()->isPublished());
        $this->get('/en/blog/toggle-me')->assertOk();

        $this->actingAs($admin)->patch('/en/admin/blog/toggle-me/unpublish');
        $this->assertFalse($post->refresh()->isPublished());
        $this->get('/en/blog/toggle-me')->assertNotFound();
    }

    public function test_a_section_holding_articles_cannot_be_deleted(): void
    {
        $section = $this->section();

        $this->livePost(['blog_category_id' => $section->id]);

        $this->actingAs($this->admin())
            ->delete('/en/admin/blog-sections/'.$section->key)
            ->assertSessionHasErrors('section');

        $this->assertDatabaseHas('blog_categories', ['id' => $section->id]);
    }

    public function test_a_moderator_can_author_but_not_manage_sections(): void
    {
        $moderator = $this->staff(User::ROLE_MODERATOR, '+966550000103');

        $this->actingAs($moderator)->get('/en/admin/blog')->assertOk();

        // Sections are behind ensure.admin: the key lands in a crawlable URL.
        $this->actingAs($moderator)
            ->get('/en/admin/blog-sections')
            ->assertRedirect()
            ->assertSessionHasErrors('access');
    }
}
