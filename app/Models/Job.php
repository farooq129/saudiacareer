<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A job listing.
 *
 * Not to be confused with a queued job: this board's queue table is
 * `queue_jobs`, so that `jobs` could name the product. Queued work lives in
 * App\Jobs and never touches this model.
 */
#[RouteKey('slug')]
#[Fillable([
    'user_id', 'company_id', 'city_id', 'category_id', 'employment_type_id',
    'slug', 'title_ar', 'title_en', 'excerpt_ar', 'excerpt_en',
    'description_ar', 'description_en',
    'salary_min', 'salary_max', 'salary_note_ar', 'salary_note_en',
    'experience_ar', 'experience_en', 'eligibility_ar', 'eligibility_en',
    'transfer_available', 'whatsapp', 'phone', 'email', 'image_path',
    'is_featured', 'is_urgent', 'status', 'published_at', 'expires_at',
])]
class Job extends Model
{
    use HasFactory, HasTranslatableColumns, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FILLED = 'filled';

    protected array $translatable = [
        'title', 'excerpt', 'description',
        'salary_note', 'experience', 'eligibility',
    ];

    protected function casts(): array
    {
        return [
            'description_ar' => 'array',
            'description_en' => 'array',
            'transfer_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_urgent' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    /** The admin who last approved, rejected or withdrew this listing. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(JobReport::class);
    }

    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    /**
     * Everything a visitor may see: published, past its publish date, and not
     * yet expired. Every public query starts here.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * The search box: title and one-line blurb, in both languages at once, so
     * an Arabic visitor typing a Latin company name still finds the listing.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('title_ar', 'like', $like)
                ->orWhere('title_en', 'like', $like)
                ->orWhere('excerpt_ar', 'like', $like)
                ->orWhere('excerpt_en', 'like', $like)
                ->orWhereHas('company', fn (Builder $c) => $c
                    ->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like));
        });
    }

    /**
     * Apply the three search-bar filters. Each is a taxonomy key, not an id,
     * so a filter survives a language switch and reads in a shareable URL.
     */
    #[Scope]
    protected function filtered(Builder $query, ?string $city, ?string $category, ?string $type): void
    {
        $query
            ->when($city && $city !== 'all', fn (Builder $q) => $q
                ->whereHas('city', fn (Builder $c) => $c->where('key', $city)))
            ->when($category && $category !== 'all', fn (Builder $q) => $q
                ->whereHas('category', fn (Builder $c) => $c->where('key', $category)))
            ->when($type && $type !== 'all', fn (Builder $q) => $q
                ->whereHas('employmentType', fn (Builder $c) => $c->where('key', $type)));
    }

    /**
     * The moderation queue: everything waiting on an admin, oldest first — a
     * poster who submitted on Monday should not sit behind one who submitted
     * on Friday.
     */
    #[Scope]
    protected function awaitingReview(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING)->oldest('created_at');
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

    /**
     * The admin search box. Wider than the public one: an admin chasing a
     * report needs to find a listing by its slug or by the employer behind it,
     * not just by what a visitor would have typed.
     */
    #[Scope]
    protected function adminSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('title_ar', 'like', $like)
                ->orWhere('title_en', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhereHas('company', fn (Builder $c) => $c
                    ->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like))
                ->orWhereHas('user', fn (Builder $u) => $u
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like));
        });
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** The contact details for this vacancy, falling back to the employer's. */
    public function contactWhatsapp(): ?string
    {
        return $this->whatsapp ?: $this->company?->whatsapp;
    }

    public function contactPhone(): ?string
    {
        return $this->phone ?: $this->company?->phone;
    }

    public function contactEmail(): ?string
    {
        return $this->email ?: $this->company?->email;
    }
}
