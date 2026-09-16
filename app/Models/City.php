<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableColumns;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[RouteKey('key')]
#[Fillable(['key', 'name_ar', 'name_en', 'region_ar', 'region_en', 'latitude', 'longitude', 'sort_order', 'is_active'])]
class City extends Model
{
    use HasFactory, HasTranslatableColumns;

    /** Bases resolved off the current locale — $city->name, $city->region. */
    protected array $translatable = ['name', 'region'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function seekerPosts(): HasMany
    {
        return $this->hasMany(JobSeekerPost::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
