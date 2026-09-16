<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

/**
 * /categories/{key} is the shareable, crawlable address of a category. It
 * redirects into the results screen with the filter applied, so there is one
 * canonical results URL rather than two pages rendering the same feed.
 */
class CategoryController extends Controller
{
    public function __invoke(Category $category): RedirectResponse
    {
        abort_unless($category->is_active, 404);

        return redirect()->to(lroute('jobs.index', ['cat' => $category->key]));
    }
}
