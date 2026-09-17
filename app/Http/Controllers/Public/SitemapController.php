<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\City;
use App\Models\Job;
use App\Support\Locale;
use Illuminate\Http\Response;

/**
 * The XML sitemap.
 *
 * Registered outside the two language groups, because a sitemap is one file for
 * the whole site rather than a page that exists twice. Each URL is emitted once
 * per language with xhtml:link alternates pointing at its counterpart — the
 * same relationship the pages declare in their own <head>, restated here so a
 * crawler learns the pairing before it fetches either one.
 *
 * Kept as a single file on purpose. A board this size is nowhere near the
 * 50,000-URL limit at which splitting into an index becomes necessary, and one
 * file is one thing to get right.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ...$this->staticPages(),
            ...$this->blog(),
            ...$this->jobs(),
            ...$this->taxonomies(),
        ];

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** @return list<array<string, mixed>> */
    private function staticPages(): array
    {
        return [
            $this->entry('home', [], null, '1.0', 'daily'),
            $this->entry('jobs.index', [], null, '0.9', 'hourly'),
            $this->entry('seekers.index', [], null, '0.7', 'daily'),
            $this->entry('blog.index', [], null, '0.8', 'daily'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function blog(): array
    {
        $urls = [];

        foreach (BlogCategory::active()->get() as $section) {
            $urls[] = $this->entry('blog.section', $section, null, '0.6', 'weekly');
        }

        /*
         * Only indexable posts. A post an editor marked noindex still renders
         * with its own robots meta, but listing it here would be telling a
         * crawler to come and read a page we asked it to ignore.
         */
        BlogPost::query()
            ->published()
            ->where('is_indexable', true)
            ->whereNull('canonical_url')
            ->latest('published_at')
            ->chunk(200, function ($posts) use (&$urls) {
                foreach ($posts as $post) {
                    $urls[] = $this->entry('blog.show', $post, $post->updated_at, '0.7', 'monthly');
                }
            });

        return $urls;
    }

    /** @return list<array<string, mixed>> */
    private function jobs(): array
    {
        $urls = [];

        Job::query()
            ->published()
            ->latest('published_at')
            ->chunk(500, function ($jobs) use (&$urls) {
                foreach ($jobs as $job) {
                    $urls[] = $this->entry('jobs.show', $job, $job->updated_at, '0.8', 'daily');
                }
            });

        return $urls;
    }

    /** @return list<array<string, mixed>> */
    private function taxonomies(): array
    {
        $urls = [];

        foreach (Category::active()->get() as $category) {
            $urls[] = $this->entry('categories.show', $category, null, '0.6', 'daily');
        }

        foreach (City::active()->get() as $city) {
            $urls[] = $this->entry('cities.show', $city, null, '0.6', 'daily');
        }

        return $urls;
    }

    /**
     * One sitemap entry per language, each carrying the full alternate set.
     *
     * @return array<string, mixed>
     */
    private function entry(
        string $name,
        mixed $parameters = [],
        mixed $lastmod = null,
        string $priority = '0.5',
        string $changefreq = 'weekly',
    ): array {
        $alternates = [];

        foreach (Locale::SUPPORTED as $locale) {
            $alternates[$locale] = Locale::route($name, $parameters, $locale);
        }

        return [
            'alternates' => $alternates,
            'lastmod' => $lastmod?->toAtomString(),
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];
    }
}
