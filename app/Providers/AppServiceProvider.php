<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Laravel's stock paginators are Tailwind markup, and this board does
        // not ship Tailwind — its look is a direct port of the design canvas.
        // One view for both, since the board never uses a simple paginator.
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');
    }
}
