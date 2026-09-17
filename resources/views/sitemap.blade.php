<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>{{--
    One <url> per language per page, each declaring the whole alternate set
    including itself — which is what the protocol asks for. A crawler that finds
    the Arabic entry learns the English URL from the same block.
--}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $url)
@foreach ($url['alternates'] as $locale => $href)
    <url>
        <loc>{{ $href }}</loc>
@foreach ($url['alternates'] as $altLocale => $altHref)
        <xhtml:link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $altHref }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $url['alternates']['ar'] ?? $href }}"/>
@if (! empty($url['lastmod']))
        <lastmod>{{ $url['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
    </url>
@endforeach
@endforeach
</urlset>
