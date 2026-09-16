<?php

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

/**
 * The header's category menu is cached for six hours. An admin who renames a
 * category expects to see it renamed, so every write drops that cache.
 */
class CategoryObserver
{
    public function saved(Category $category): void
    {
        Cache::forget('nav.categories');
    }

    public function deleted(Category $category): void
    {
        Cache::forget('nav.categories');
    }
}
