<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A blog article.
 *
 * Visibility is the same two-part rule the board uses everywhere: status says
 * what the editor intends, published_at says when the public may see it. A post
 * dated forward is scheduled, not live, and no cron is needed to flip it — the
 * published scope simply starts matching it.
 */
#[RouteKey('slug')]
#[Fillable([
    'blog_category_id', 'user_id', 'author_name', 'slug',
    'title_ar', 'title_en', 'excerpt_ar', 'excerpt_en', 'body_ar', 'body_en',
    'image_path', 'image_alt_ar', 'image_alt_en',
    'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en',
    'is_indexable', 'canonical_url', 'is_featured', 'status', 'published_at',
])]
class BlogPost extends Model
{
    use HasTranslatableColumns, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /** Words a minute, for the "5 min read" line. Deliberately conservative. */
    private const READING_SPEED = 200;

    protected array $translatable = [
        'title', 'excerpt', 'body', 'image_alt', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'body_ar' => 'array',
            'body_en' => 'array',
            'is_indexable' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ── Scopes ─────────────────────────────────────────────────────────── */

    /**
     * Everything a visitor may see. Every public query starts here.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    #[Scope]
    protected function inSection(Builder $query, ?string $key): void
    {
        $query->when(
            $key && $key !== 'all',
            fn (Builder $q) => $q->whereHas('category', fn (Builder $c) => $c->where('key', $key)),
        );
    }

    /** The reader's search box: headline and blurb, both languages at once. */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(fn (Builder $q) => $q
            ->where('title_ar', 'like', $like)
            ->orWhere('title_en', 'like', $like)
            ->orWhere('excerpt_ar', 'like', $like)
            ->orWhere('excerpt_en', 'like', $like));
    }

    /** Narrow the admin list to one status; 'all' or null shows every one. */
    #[Scope]
    protected function ofStatus(Builder $query, ?string $status): void
    {
        $query->when(
            $status && $status !== 'all',
            fn (Builder $q) => $q->where('status', $status),
        );
    }

    /** Wider than the reader's box: an editor looks things up by slug too. */
    #[Scope]
    protected function adminSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(fn (Builder $q) => $q
            ->where('title_ar', 'like', $like)
            ->orWhere('title_en', 'like', $like)
            ->orWhere('slug', 'like', $like)
            ->orWhere('author_name', 'like', $like));
    }

    /* ── Reading ────────────────────────────────────────────────────────── */

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /** Live, but dated forward — the editor scheduled it. */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    /** The body as one string, for word counts and meta fallbacks. */
    public function bodyText(?string $locale = null): string
    {
        $paragraphs = $this->translate('body', $locale);

        return is_array($paragraphs) ? implode(' ', $paragraphs) : (string) $paragraphs;
    }

    /** Minutes to read, never less than one. */
    public function readingMinutes(?string $locale = null): int
    {
        $words = Str::of($this->bodyText($locale))->wordCount();

        return max(1, (int) ceil($words / self::READING_SPEED));
    }

    /* ── SEO ────────────────────────────────────────────────────────────────
       Each of these answers "what should the crawler see", and each falls back
       down a chain rather than ever returning empty — a blank <title> or a
       missing og:description is worse than a repeated one. */

    public function metaTitle(?string $locale = null): string
    {
        return (string) ($this->translate('meta_title', $locale) ?: $this->translate('title', $locale));
    }

    public function metaDescription(?string $locale = null): string
    {
        $explicit = $this->translate('meta_description', $locale);

        if ($explicit) {
            return (string) $explicit;
        }

        $excerpt = $this->translate('excerpt', $locale);

        return (string) Str::limit($excerpt ?: $this->bodyText($locale), 155);
    }

    public function imageAlt(?string $locale = null): string
    {
        return (string) ($this->translate('image_alt', $locale) ?: $this->translate('title', $locale));
    }

    /** The byline, falling back to the account's name. */
    public function byline(): ?string
    {
        return $this->author_name ?: $this->author?->name;
    }
}
