User-agent: *
Allow: /

# Signed-in areas hold nothing a crawler should index, and following these
# would only burn crawl budget on login redirects.
Disallow: /admin
Disallow: /en/admin
Disallow: /employer
Disallow: /en/employer
Disallow: /seeker
Disallow: /en/seeker
Disallow: /saved
Disallow: /en/saved

# Filtered result sets are the same listings in a different order — the
# canonical pages are the listing, category and city URLs the sitemap names.
Disallow: /*?q=
Disallow: /*?page=

Sitemap: {{ url('sitemap.xml') }}
