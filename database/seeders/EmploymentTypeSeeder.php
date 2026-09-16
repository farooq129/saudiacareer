<?php

namespace Database\Seeders;

use App\Models\EmploymentType;
use Illuminate\Database\Seeder;

class EmploymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'key' => 'full',
                'name_ar' => 'دوام كامل',
                'name_en' => 'Full-time',
                'sort_order' => 10,
            ],
            [
                'key' => 'contract',
                'name_ar' => 'عقد مؤقت',
                'name_en' => 'Contract',
                'sort_order' => 20,
            ],
            [
                'key' => 'part',
                'name_ar' => 'دوام جزئي',
                'name_en' => 'Part-time',
                'sort_order' => 30,
            ],
            [
                'key' => 'remote',
                'name_ar' => 'عن بُعد',
                'name_en' => 'Remote',
                'sort_order' => 40,
            ],
        ];

        foreach ($types as $type) {
            EmploymentType::updateOrCreate(['key' => $type['key']], $type);
        }
    }
}
