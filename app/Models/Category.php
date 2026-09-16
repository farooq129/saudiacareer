<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableColumns;
use App\Observers\CategoryObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(CategoryObserver::class)]
#[RouteKey('key')]
#[Fillable([
    'key', 'name_ar', 'name_en', 'icon', 'svg_path',
    'art_from', 'art_to', 'art_ink', 'sort_order', 'is_active',
])]
class Category extends Model
{
    use HasFactory, HasTranslatableColumns;

    protected array $translatable = ['name'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function seekerPosts(): HasMany
    {
        return $this->hasMany(JobSeekerPost::class);
    }

    /**
     * The gradient a listing card falls back to when it has no photo.
     *
     * @return array{from: string, to: string, ink: string}
     */
    public function art(): array
    {
        return [
            'from' => $this->art_from ?? '#e2e8f0',
            'to' => $this->art_to ?? '#cbd5e1',
            'ink' => $this->art_ink ?? '#334155',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
