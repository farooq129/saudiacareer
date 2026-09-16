<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

/**
 * The nine cities the board launches with, in the order the design canvas
 * ranks them — by listing volume, which is also the order a Saudi job seeker
 * expects to see them in.
 *
 * jobs_count is the seeded figure from the canvas, so the home page's city
 * rollup has something to show before real listings accumulate. The board
 * recomputes it from the listings table once it is live.
 */
class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            [
                'key' => 'riyadh',
                'name_ar' => 'الرياض',
                'name_en' => 'Riyadh',
                'jobs_count' => 4820,
                'sort_order' => 10,
            ],
            [
                'key' => 'jeddah',
                'name_ar' => 'جدة',
                'name_en' => 'Jeddah',
                'jobs_count' => 2740,
                'sort_order' => 20,
            ],
            [
                'key' => 'dammam',
                'name_ar' => 'الدمام',
                'name_en' => 'Dammam',
                'jobs_count' => 1360,
                'sort_order' => 30,
            ],
            [
                'key' => 'khobar',
                'name_ar' => 'الخبر',
                'name_en' => 'Khobar',
                'jobs_count' => 1105,
                'sort_order' => 40,
            ],
            [
                'key' => 'makkah',
                'name_ar' => 'مكة المكرمة',
                'name_en' => 'Makkah',
                'jobs_count' => 864,
                'sort_order' => 50,
            ],
            [
                'key' => 'madinah',
                'name_ar' => 'المدينة المنورة',
                'name_en' => 'Madinah',
                'jobs_count' => 712,
                'sort_order' => 60,
            ],
            [
                'key' => 'buraidah',
                'name_ar' => 'بريدة',
                'name_en' => 'Buraidah',
                'jobs_count' => 468,
                'sort_order' => 70,
            ],
            [
                'key' => 'tabuk',
                'name_ar' => 'تبوك',
                'name_en' => 'Tabuk',
                'jobs_count' => 262,
                'sort_order' => 80,
            ],
            [
                'key' => 'abha',
                'name_ar' => 'أبها',
                'name_en' => 'Abha',
                'jobs_count' => 149,
                'sort_order' => 90,
            ],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(['key' => $city['key']], $city);
        }
    }
}
