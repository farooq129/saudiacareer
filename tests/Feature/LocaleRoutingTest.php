<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Database\Seeders\CitySeeder;
use Database\Seeders\EmploymentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two-language routing is the part of this app most likely to break
 * quietly: a route added to routes/board.php is published twice automatically,
 * so a mistake shows up as one language working and the other 404ing.
 */
class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The chrome reads the taxonomy on every page.
        $this->seed(CitySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(EmploymentTypeSeeder::class);
    }

    public function test_the_bare_root_serves_arabic_right_to_left(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('lang="ar"', escape: false)
            ->assertSee('dir="rtl"', escape: false);
    }

    public function test_the_en_prefix_serves_english_left_to_right(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('lang="en"', escape: false)
            ->assertSee('dir="ltr"', escape: false);
    }

    public function test_every_board_route_exists_in_both_languages(): void
    {
        $named = collect(app('router')->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn (?string $name) => $name !== null && str_starts_with($name, 'ar.'))
            ->map(fn (string $name) => substr($name, 3));

        $this->assertNotEmpty($named, 'No Arabic routes were registered at all.');

        foreach ($named as $bare) {
            $this->assertTrue(
                app('router')->has('en.'.$bare),
                "Route [{$bare}] exists in Arabic but not in English.",
            );
        }
    }

    public function test_a_stored_english_preference_only_redirects_the_bare_root(): void
    {
        // A returning English reader typing the domain lands in English…
        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertRedirect(route('en.home'));

        // …but a shared Arabic deep link is never bounced out of Arabic.
        $this->withSession(['locale' => 'en'])
            ->get('/jobs')
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false);
    }

    public function test_the_language_switcher_keeps_your_place_and_your_filters(): void
    {
        $response = $this->get('/jobs?city=riyadh&cat=drivers');

        $response->assertOk();

        // The switcher points at the same screen in English, filters intact —
        // not at the English home page. The query string comes back
        // alphabetised, because that is what Request::getQueryString() does;
        // it is a normalisation, so the same filters always produce one URL.
        $response->assertSee('/en/jobs?cat=drivers&amp;city=riyadh', escape: false);
    }

    public function test_a_category_url_redirects_into_the_filtered_results_in_its_own_language(): void
    {
        $this->get('/categories/drivers')
            ->assertRedirect(route('ar.jobs.index', ['cat' => 'drivers']));

        $this->get('/en/categories/drivers')
            ->assertRedirect(route('en.jobs.index', ['cat' => 'drivers']));
    }
}
