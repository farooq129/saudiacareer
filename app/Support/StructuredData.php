<?php

namespace App\Support;

use App\Models\BlogPost;
use Illuminate\Support\Collection;

/**
 * schema.org payloads for the pages that publish them.
 *
 * Built here rather than in a Blade for one specific reason: `@context` is a
 * Blade directive. Written inline in a template it compiles to PHP and the
 * page ships a script block whose first key is a chunk of source code — valid
 * JSON, meaningless to a crawler, and invisible unless you read the rendered
 * output. Assembling the arrays in PHP and handing the view a finished
 * structure removes the whole class of problem.
 */
class StructuredData
{
    /** An article, with its author and publisher. */
    public static function blogPosting(BlogPost $post, string $image, string $url): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->metaDescription(),
            'image' => [$image],
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'inLanguage' => Locale::current(),
            // str_word_count only counts Latin words, so an Arabic-only body
            // would report zero. Better to omit the claim than to publish a
            // wrong one.
            'wordCount' => str_word_count($post->bodyText()) ?: null,
            'articleSection' => $post->category?->name,
            'author' => $post->byline() ? [
                '@type' => 'Person',
                'name' => $post->byline(),
            ] : null,
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('board.brand.'.Locale::current()),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/main_logo.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $post->canonical_url ?: $url,
            ],
        ]);
    }

    /** The blog feed, or one section of it, as a collection of articles. */
    public static function blog(string $name, string $description, string $url, Collection $posts): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'inLanguage' => Locale::current(),
            'blogPost' => $posts->map(fn (BlogPost $post) => array_filter([
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'url' => lroute('blog.show', $post),
                'datePublished' => $post->published_at?->toAtomString(),
            ]))->values()->all(),
        ];
    }

    /**
     * A breadcrumb trail. Each crumb is [name, url]; the last one carries no
     * url, because the page you are on is not a link to somewhere else.
     *
     * @param  list<array{0: string, 1: string|null}>  $crumbs
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        $position = 1;

        foreach ($crumbs as [$name, $url]) {
            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => $url,
            ]);
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /** One <script> body, ready to drop into a template. */
    public static function json(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
