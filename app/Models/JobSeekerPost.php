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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A seeker advertising themselves. The mirror image of a Job, and rendered by
 * the same card component.
 */
#[RouteKey('slug')]
#[Fillable([
    'user_id', 'city_id', 'category_id', 'employment_type_id', 'slug',
    'display_name_ar', 'display_name_en', 'headline_ar', 'headline_en',
    'pitch_ar', 'pitch_en', 'experience_ar', 'experience_en',
    'expected_salary_min', 'expected_salary_max',
    'transfer_available', 'available_immediately', 'cv_path',
    'whatsapp', 'phone', 'email', 'status', 'published_at', 'expires_at',
])]
class JobSeekerPost extends Model
{
    use HasFactory, HasTranslatableColumns, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_HIRED = 'hired';

    protected array $translatable = ['display_name', 'headline', 'pitch', 'experience'];

    protected function casts(): array
    {
        return [
            'transfer_available' => 'boolean',
            'available_immediately' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** The admin who last approved, rejected or withdrew this post. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    #[Scope]
    protected function filtered(Builder $query, ?string $city, ?string $category): void
    {
        $query
            ->when($city && $city !== 'all', fn (Builder $q) => $q
                ->whereHas('city', fn (Builder $c) => $c->where('key', $city)))
            ->when($category && $category !== 'all', fn (Builder $q) => $q
                ->whereHas('category', fn (Builder $c) => $c->where('key', $category)));
    }

    /** @see Job::awaitingReview() — the two queues work the same way. */
    #[Scope]
    protected function awaitingReview(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING)->oldest('created_at');
    }

    #[Scope]
    protected function ofStatus(Builder $query, ?string $status): void
    {
        $query->when(
            $status && $status !== 'all',
            fn (Builder $q) => $q->where('status', $status),
        );
    }

    #[Scope]
    protected function adminSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('headline_ar', 'like', $like)
                ->orWhere('headline_en', 'like', $like)
                ->orWhere('display_name_ar', 'like', $like)
                ->orWhere('display_name_en', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhereHas('user', fn (Builder $u) => $u
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like));
        });
    }

    /**
     * How an employer reaches this seeker, falling back to the account behind
     * the post.
     *
     * The per-post fields exist so a seeker can publish a number that is not
     * their login one — a second SIM, or a relative's phone. Most leave them
     * empty, so without the fallback the contact panel would be blank on nearly
     * every post, which is the one thing it is there to prevent.
     */
    public function contactWhatsapp(): ?string
    {
        return $this->whatsapp ?: $this->user?->phone;
    }

    public function contactPhone(): ?string
    {
        return $this->phone ?: $this->user?->phone;
    }

    public function contactEmail(): ?string
    {
        return $this->email ?: $this->user?->email;
    }

    public function hasContact(): bool
    {
        return (bool) ($this->contactWhatsapp() || $this->contactPhone() || $this->contactEmail());
    }

    /** First letter of the seeker's name, for the card monogram. */
    public function monogram(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $name = trim((string) $this->translate('display_name', $locale));
        $first = preg_split('/\s+/u', $name)[0] ?? '';

        if ($first === '') {
            return '؟';
        }

        $initial = mb_substr($first, 0, 1);

        return $locale === 'en' ? mb_strtoupper($initial) : $initial;
    }
}
