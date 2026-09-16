<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\RedirectResponse;

/** @see CategoryController — same reasoning, keyed on city. */
class CityController extends Controller
{
    public function __invoke(City $city): RedirectResponse
    {
        abort_unless($city->is_active, 404);

        return redirect()->to(lroute('jobs.index', ['city' => $city->key]));
    }
}
