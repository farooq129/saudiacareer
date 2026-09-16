# Mihna — مهنة

A Saudi job board, in Arabic and English. Laravel 13, Blade, Alpine, MySQL.

The design is decided in `design/Mihna Jobs Light.dc.html`, a canvas of the three
public screens. The public board is the port of that canvas — when the two
disagree about how something should look or read, the canvas is right. The
signed-in areas (admin, employer, seeker) have no canvas; they are built from the
same design system, in the same class vocabulary.

---

## Getting started

Requirements: PHP 8.3+, Composer 2, Node 20+, MySQL 8 (or MariaDB 10.6+).

```bash
composer install
cp .env.example .env
php artisan key:generate

# Create the database first:
#   CREATE DATABASE saudiacareer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate --seed

npm install
npm run build        # or: npm run dev
php artisan serve
```

`composer setup` runs all of the above in one go.

The default seed installs only what the board cannot work without: the
taxonomies, the roles and permissions, and the first admin.

To get a board with something on it, there are two optional seeders:

```bash
# The ten listings and seven seeker posts from the design canvas.
php artisan db:seed --class=CanvasContentSeeder

# The above, plus eleven more listings, twelve seeker accounts, applications at
# every stage, saved ads and a reports queue — every table populated, and a
# spread of statuses so each screen in all three areas has something to show.
php artisan db:seed --class=DemoDataSeeder
```

Both are fictional and are deliberately kept out of the default seed — every
employer and every applicant in them is invented. Do not run either anywhere
real. Both are idempotent, so running them twice does not double the board.

The demo seeder logs in with any of its accounts at password `password`; find
one with `php artisan tinker` and query for the role you want.

To get back to a clean board at any point:

```bash
php artisan migrate:fresh --seed
```

That drops everything and leaves only the taxonomies, the roles and permissions,
and the admin. Nine cities, sixteen categories, four employment types, three
roles, fifteen permissions, one account, and no listings.

**The first admin.** `AdminUserSeeder` will not invent a password. Set
`ADMIN_PHONE` and `ADMIN_PASSWORD` in `.env` before seeding, or it skips itself
and says so.

---

## The two languages

This is the part of the codebase worth reading before you change anything.

Arabic is the default and is served from the bare root. English is served from
`/en`. **Both are real, crawlable URLs** — English is not a query-string variant
or a session flag — which is what lets an Arabic listing and its English
translation each rank on their own, and what makes a shared link land the
recipient in the language the sender was reading.

```
/                    الرئيسية            /en                  Home
/jobs?cat=drivers    نتائج البحث          /en/jobs?cat=drivers Results
/jobs/{slug}         تفاصيل الوظيفة       /en/jobs/{slug}      Listing
```

### How that is wired

`routes/board.php` declares every public page **once**. `routes/web.php` loads
that file twice — bare under the `ar.` name prefix, and under `/en` with the
`en.` prefix. So `ar.jobs.show` and `en.jobs.show` are the same page in the two
languages. `routes/auth.php` does the same for `routes/auth-pages.php`.

Three rules follow, and a test enforces the first two:

1. **Never name a locale inside `routes/board.php`.** Add the route once; both
   languages appear automatically.
2. **Never call `route()` in a view.** Call `lroute('jobs.show', $job)`, which
   resolves against the language the request is running in. `route('ar.jobs.show')`
   in a Blade hard-codes Arabic into an English page.
3. **Name every route, including POSTs.** An unnamed route inside a `->name('en.')`
   group registers as plain `en.`, which then collides with the next one.

`App\Http\Middleware\SetLocale` reads the language back off the URL prefix, so
the URL stays the single source of truth. A stored preference is consulted in
exactly one place — the bare root, which is the only URL that says nothing about
language. A shared deep link is never redirected out of its language.

`App\Support\Locale::urlIn($locale)` gives you the page you are on in the other
language, query string and all. That is what the language switcher links to and
what the `hreflang` tags point at.

### Where the words live

Interface copy is in `lang/ar/*.php` and `lang/en/*.php`, one file per area
(`nav`, `home`, `search`, `results`, `detail`, `footer`, `units`, …), generated
from the canvas and keyed identically to it. Loose strings used from controllers
are in `lang/ar.json`.

Content — listing titles, company names, seeker headlines — is **two columns per
field**, `title_ar` and `title_en`, not a JSON blob. That is so MySQL can index
and full-text search each language on its own. `App\Models\Concerns\HasTranslatableColumns`
keeps that out of the views: a model lists its translatable bases and Blades read
`$job->title`, which resolves to the current locale and falls back to the other
language rather than rendering blank.

### Arabic plurals

Arabic has six plural categories where English has two, and Laravel's
`trans_choice()` speaks the English shape. Use `App\Support\Plural`:

```php
Plural::of($jobs->total(), 'results.count');   // وظيفتان مطابقتان  /  2 matching jobs
Plural::ago($minutes);                         // قبل ساعتين        /  2 hours ago
```

The language files carry four keys — `one`, `two`, `few`, `many` — in both
languages, so one call site serves both. English fills `one` with the singular
and the rest with the plural.

### Writing RTL-safe CSS

Use logical properties everywhere: `margin-inline-start`, `padding-inline-end`,
`inset-inline-start`, `text-align: start`. One stylesheet then serves both
directions and nothing has to be mirrored by hand. Say `left`/`right` only where
you genuinely mean a physical side — there are two such places, both commented:
the mobile drawer (a fixed box resolves logical insets against its own writing
mode, not the page's) and the hero scrim's light pool.

Three exceptions worth knowing:

- Phone numbers and email addresses carry `dir="ltr"` and read left-to-right in
  both languages — on the inputs while typing, and on the contact dialog's values.
- Numbers stay Western in both languages (`9,000 – 12,000 ريال`). Arabic-Indic
  digits in a salary read as a typo to most of this audience.
- Arrows and breadcrumb carets are chosen in PHP per direction (`ph-arrow-left`
  vs `ph-arrow-right`), because an arrow has to point the way reading goes. The
  guillemet on a mega-menu heading is the exception: it is bidi-mirrored by the
  browser, so it must NOT get an RTL override.

---

## Layout

```
app/
  Http/Controllers/
    Public/      the board itself — home, results, listing, seekers, saved
    Auth/        sign in, register, sign out
    Admin/       moderation queues, employer verification, accounts
    Employer/    their own listings, applicants, company profile
    Seeker/      their own posts, applications, profile
  Http/Middleware/
    SetLocale    decides the request language from the URL
    EnsureRole   gates an area to one or more account roles
  Models/
    Concerns/HasTranslatableColumns
  Services/      SavedJobService, RoleRouteResolver,
                 ListingModerator (approve/reject), PortalNavigator (sidebars)
  Support/       Locale, Phone, Plural, helpers.php
config/board.php  brand, AI tool keys, footer structure, listing lifecycle
database/
  data/          canvas-content.json, extra-listings.json — the sample board
  migrations/    schema
  seeders/       taxonomy, roles, admin, sample content, demo activity
lang/{ar,en}/    interface copy, one file per area
resources/css/app.css  the whole design system, ported from the canvas
resources/views/
  layouts/app    the public shell: dir, hreflang, fonts, the Alpine root
  layouts/portal the signed-in shell, shared by all three areas
  partials/      header, drawer, footer, job-row, seeker-row, pagination
  public/        home, results, show, saved, seekers/
  admin/         dashboard, jobs/, seekers/, companies/, reports/, users/
  employer/      dashboard, jobs/, applications/, company/
  seeker/        dashboard, posts/, applications/, profile/
  auth/          login, register
  errors/        404
design/          the source design canvas — not served
routes/
  web.php        loads the three route files twice, once per language
  board.php      every public page, declared once
  admin.php      the admin area
  portal.php     the employer and seeker areas
  auth.php       loads auth-pages.php twice
```

---

## Six things that will surprise you

**`jobs` is the listings table, not the queue.** Laravel's queue table is
`queue_jobs` here, renamed in `config/queue.php`, so that `jobs` could name the
product. Queued work still lives in `App\Jobs` and never touches
`App\Models\Job`.

**You cannot cache an Eloquent model.** Laravel 13 refuses to unserialize any
class out of cache storage unless it is listed in `cache.serializable_classes`,
which defaults to `false`. Caching a model silently returns
`__PHP_Incomplete_Class`. Cache the raw rows and rehydrate — see
`ViewServiceProvider::navCategories()` for the pattern. Don't widen the config to
get around it.

**A 404 is served by a fallback route, not by the exception handler alone.** A
URL that matches nothing never enters the web group, so it has no session — and
the chrome needs one for the saved-ads badge and the CSRF token. `Route::fallback()`
in `routes/web.php` makes the 404 a matched route, so it renders as a real page
with the header and footer on it.

**Never pass a possibly-null value as the second argument to `@section`.**
Blade reads a null there as the *block* form and calls `ob_start()` with nothing
to close it, so every render of that page leaks an output buffer. It is silent:
`@section('description', $job->excerpt)` on a listing with no excerpt did this
for a while, and only PHPUnit marking the test "risky" caught it. Use `?:` with
a real fallback.

**The framework's two auth redirects have to be configured.** Laravel sends
guests to a bare `route('login')` and signed-in users to `/dashboard`. Neither
exists here, because every route is registered twice under `ar.` and `en.` — so
both defaults threw a RouteNotFoundException and served a 500 where a redirect
belonged. `redirectGuestsTo` and `redirectUsersTo` in `bootstrap/app.php` replace
them, reading the language off the URL so an English visitor bounced off
`/en/admin` lands on the English sign-in page and is returned to `/en/admin`
afterwards.

**`public/hot` decides where the front end comes from.** `npm run dev` writes it
and removes it on a clean exit; kill the process and it is left behind. While it
exists, `@vite` emits script and style tags pointing at the dev server instead of
the built files. That is what you want while developing — but if the dev server
is not actually running, the page loads with no CSS and no Alpine, and every
menu, dialog and toggle quietly does nothing. If the site looks unstyled or
nothing is clickable, check whether `npm run dev` is alive; if it is not, delete
`public/hot` and run `npm run build`.

Note the file records the address vite bound to, which is often `http://[::1]:5173`
— IPv6. Checking `127.0.0.1:5173` will report nothing listening even when the
server is up.

---

## Schema

| Table | What it holds |
|---|---|
| `users` | Every account. `role` is admin / employer / seeker. Phone-first: `phone` is the unique login handle, `email` is optional. |
| `companies` | Employers. `is_verified` is the badge an admin grants after checking the CR. |
| `cities`, `categories`, `employment_types` | The three filter axes. Keyed (`riyadh`, `drivers`, `full`) so filters survive a language switch. |
| `jobs` | Listings. Bilingual columns; description / requirements / benefits are JSON arrays of paragraphs. |
| `job_seeker_posts` | People advertising themselves. Same shape as a listing, so one card renders both. |
| `applications` | `source` separates an on-site application (carries a CV) from a WhatsApp tap. |
| `saved_jobs` | The saved list. Works signed out — see `SavedJobService`. |
| `job_reports` | Moderation queue. Open to signed-out visitors, because fee scams are met before anyone makes an account. |

A listing is visible only when `status = published` **and** `published_at <= now`
**and** (`expires_at` is null **or** in the future). That is the `published()`
scope; every public query starts there.

---

## Testing

```bash
php artisan test        # or: vendor/bin/phpunit
vendor/bin/pint         # formatting
```

Tests run against in-memory SQLite. The MySQL-only parts of the schema (the
`FULLTEXT` indexes) are guarded by a driver check in the migrations.

---

## Styling

**There is no Tailwind here, and adding it would be a mistake.** The board's
look is a direct port of the design canvas and the Nocturne design system it is
built on: plain CSS, its own tokens, its own component classes, all in
`resources/css/app.css` in the same order and with the same names the canvas
uses. An earlier pass re-expressed the design in utility classes, and the
screens drifted apart from one another immediately — the results and detail
pages stopped looking like the home page. One vocabulary means a change made on
the canvas can be carried across by hand rather than translated first.

So: style with the existing classes (`.card`, `.btn`, `.btn-blue`, `.btn-cta`,
`.input`, `.field`, `.tag`, `.list-panel`, `.list-row`, `.rail`, `.mono`,
`.dialog`), and with the tokens (`var(--space-6)`, `var(--radius-sm)`,
`var(--color-accent-700)`). Inline `style=` for one-off layout is what the canvas
does and is fine. Add a class only when there is a hover or focus state, since
the canvas writes those as a `style-hover` attribute its own runtime applies and
they have to become real CSS here.

Two things carried over from the canvas that you may want to change deliberately
rather than by accident:

- **The accent colour differs by language.** Arabic runs the design system's
  purple (`#5d5294`), English the brand navy — the section rails, focus rings
  and kickers change colour when you switch language. That is what the canvas
  does. If the brand navy should carry both, override the `--color-accent*` ramp
  on `.app` rather than only on `.app[lang='en']`.
- **English is themed onto Proxima Nova, which is not loaded.** It is a licensed
  face and stays first in the stack, so adding an Adobe Fonts kit is the only
  thing needed to switch English over. Until then it falls back to IBM Plex Sans
  Arabic rather than Arial: Plex is already self-hosted for the Arabic side, its
  Latin is good, and the alternative was English rendering in a system face while
  Arabic rendered in a webfont — two textures in one product.

Icons are Phosphor (`<i class="ph ph-map-pin">`), installed from npm and
self-hosted. Fonts are self-hosted too, built by the Vite font plugin.

---

## State of the port

Built and working in both languages:

**The board** — home, results, listing detail, the seeker feed and a seeker
post, saved ads, sign in, register, a real 404. The chrome (utility strip,
header, category mega menu, AI menu, mobile drawer, footer) is shared by every
public page.

**Admin** (`/admin`) — a dashboard that opens on the queues; job
listings and seeker posts with status tabs, filters, bulk approve/reject and a
full bilingual detail screen; employer verification; the reports queue; and
accounts. Approving, rejecting, withdrawing and closing all run through
`App\Services\ListingModerator`, which records who did it and when. Everyone who
works here has a **My account** page for their own name and password.

The area has two gates, not one:

| | `ensure.staff` (admin + moderator) | `ensure.admin` only |
|---|---|---|
| | review queues, reports, employer directory, own account | verified badge, suspending accounts, adding staff |

The split is deliberate: everything admin-only is either hard to undo or easy to
abuse quietly. Approving an ad is neither — it is visible, reversible, and the
moderator's actual job. **Staff** (`/admin/staff`, admin only) adds and removes
administrators and moderators, changes roles, and sets a colleague's password.
Four guards there stop the board being left unadministerable: nobody changes
their own role, nobody removes themselves, the last admin cannot be demoted or
deleted, and new accounts get a password you type rather than one the system
invents.

**Employer** (`/employer`, `ensure.employer`) — dashboard, post and edit
listings, applicants across all listings or within one, and the company profile
that feeds the verified badge.

**Seeker** (`/seeker`, `ensure.seeker`) — a dashboard led by shortlisted and
viewed rather than applications sent, own posts, applications showing whether the
employer has opened them, saved ads, and profile with a password change.

All three signed-in areas share `layouts/portal.blade.php` and differ only in the
sidebar, which `App\Services\PortalNavigator` decides.

### The moderation cycle

`config('board.listing.moderated')` is on by default. With it on:

1. An employer or seeker submits — the listing is `pending`, not on the board.
2. An admin approves — status becomes `published`, and the publish window opens
   **now** rather than at whatever date the draft carried, running for
   `LISTING_LIFETIME_DAYS`.
3. Editing something already live sends it back to `pending`. Without that, an
   employer could get an innocuous ad approved and then rewrite it.
4. A rejection **requires** a reason, and that reason is shown back to the poster
   on their own listings page.

Ownership is checked on the row, not just on the route: an employer who guesses
another employer's slug gets a 404. `ModerationTest` covers all of it.

Unbuilt: the AI tools (the menu and its copy exist, the links go nowhere),
serving CVs through a signed route (the upload works and the path is recorded),
and email or SMS notifications.

---

## Signing in

`php artisan migrate --seed` creates exactly one account: the admin, from
`ADMIN_PHONE` and `ADMIN_PASSWORD` in `.env`. `AdminUserSeeder` skips itself and
says so if either is blank — it will not invent a password.

Everything else is made through the product. Register at `/register` and pick a
side: **I am looking for work** creates a seeker, **I am hiring** creates an
employer. The "Place an Ad" button links to `/register?as=employer`, which
preselects it.

There are no seeded test logins. An account that exists only because a seeder
made it is an account nobody remembers the password for and nobody dares delete.

To sanity-check the whole cycle by hand:

1. Register an employer, fill in the company profile, post a listing. It lands in
   `pending` and is not on the board.
2. Sign in as the admin, find it under **Job listings → Awaiting review**, and
   approve it. It appears at `/jobs/{slug}`.
3. Register a seeker, open the listing, and apply.
4. Back as the employer, the applicant shows under **Applicants** and opening
   them marks it read — which is what the seeker then sees on their own
   applications page.
