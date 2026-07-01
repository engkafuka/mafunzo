<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            ['name' => 'Weights and Measures Inspector', 'code' => 'WMI-101'],
            ['name' => 'Quality Assurance in Weighing', 'code' => 'QAW-102'],
            ['name' => 'Documentation and Compliance', 'code' => 'DOC-103'],
            ['name' => 'Store and Collateral Management', 'code' => 'SCM-104'],
        ];
        foreach ($courses as $c) {
            \App\Models\Course::firstOrCreate(
                ['code' => $c['code']],
                ['name' => $c['name'], 'is_active' => true]
            );
        }
    }
}
