<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\PortalNavigator;
use App\Services\SavedJobService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Data the chrome needs on every page.
 *
 * The board's header and the portal sidebar both appear on every page of their
 * respective areas, so they are bound once here rather than fetched inside the
 * partial — a query in a Blade is invisible to the controller that has to make
 * the page fast.
 */
class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(['partials.header', 'partials.drawer', 'partials.footer'], function ($view) {
            $view->with([
                'navCategories' => $this->navCategories(),
                'savedCount' => app(SavedJobService::class)->count(request()),
            ]);
        });

        /*
         * The signed-in areas — admin, employer, seeker — share one layout and
         * differ only in their sidebar. PortalNavigator decides which one, and
         * the badge counts are not cached: a badge that lags behind the queue
         * it describes is worse than a handful of cheap indexed counts.
         */
        View::composer('layouts.portal', function ($view) {
            $user = request()->user();

            $nav = $user
                ? app(PortalNavigator::class)->for($user)
                : ['title' => '', 'items' => []];

            $view->with([
                'portalTitle' => $nav['title'],
                'portalNav' => $nav['items'],
            ]);
        });
    }

    /**
     * The category menu, cached.
     *
     * Only the raw attribute rows go into the cache, and models are rehydrated
     * from them on the way out. Laravel 13 refuses to unserialize any class
     * from cache storage unless it is named in cache.serializable_classes — a
     * deliberate hardening, since a cache is a place an attacker who reaches
     * the database can plant an object graph. Caching plain arrays keeps that
     * default intact rather than widening it for a convenience.
     *
     * Invalidated by the Category observer on every write.
     */
    private function navCategories()
    {
        $rows = Cache::remember(
            'nav.categories',
            now()->addHours(6),
            fn () => Category::active()->get()->map->getAttributes()->all(),
        );

        return Category::hydrate($rows);
    }
}
