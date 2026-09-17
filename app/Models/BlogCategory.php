<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A blog section. Bound by `key` so /blog/section/career-advice reads the same
 * in both languages.
 */
#[RouteKey('key')]
#[Fillable(['key', 'name_ar', 'name_en', 'blurb_ar', 'blurb_en', 'sort_order', 'is_active'])]
class BlogCategory extends Model
{
    use HasTranslatableColumns;

    protected array $translatable = ['name', 'blurb'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
