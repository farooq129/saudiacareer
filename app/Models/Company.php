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

#[RouteKey('slug')]
#[Fillable([
    'user_id', 'slug', 'name_ar', 'name_en', 'logo_path', 'blurb_ar', 'blurb_en',
    'website', 'city_id', 'cr_number', 'staff_count', 'member_since',
    'whatsapp', 'phone', 'email', 'is_active',
])]
class Company extends Model
{
    use HasFactory, HasTranslatableColumns, SoftDeletes;

    protected array $translatable = ['name', 'blurb'];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'staff_count' => 'integer',
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * The initial for the card monogram.
     *
     * Latin names take their initial from the Latin spelling and Arabic names
     * from the Arabic one, so the monogram stays readable in whichever language
     * is showing. Leading words that say nothing about the employer — شركة,
     * مجموعة, "Al", "The" — are dropped first.
     */
    public function monogram(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $name = (string) $this->translate('name', $locale);

        $clean = preg_replace('/^(شركة|مجموعة|مركز|مدارس|مدرسة|صالون)\s+/u', '', $name);
        $clean = trim((string) preg_replace('/^(Al|The)\s+/iu', '', (string) $clean));

        $first = preg_split('/\s+/u', $clean)[0] ?? '';

        if ($first === '') {
            return '؟';
        }

        $initial = mb_substr($first, 0, 1);

        return $locale === 'en' ? mb_strtoupper($initial) : $initial;
    }

    #[Scope]
    protected function verified(Builder $query): void
    {
        $query->where('is_verified', true);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
