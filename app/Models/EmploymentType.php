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
#[Fillable(['key', 'name_ar', 'name_en', 'sort_order', 'is_active'])]
class EmploymentType extends Model
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

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order');
    }
}
