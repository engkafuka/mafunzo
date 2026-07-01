<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $sessionYear = (int) date('Y');
        $courses = [
            ['name' => 'Weights and Measures Inspector', 'code' => 'WMI-101'],
            ['name' => 'Quality Assurance in Weighing', 'code' => 'QAW-102'],
            ['name' => 'Documentation and Compliance', 'code' => 'DOC-103'],
            ['name' => 'Store and Collateral Management', 'code' => 'SCM-104'],
        ];

        foreach ($courses as $c) {
            Course::firstOrCreate(
                ['code' => $c['code'], 'session_year' => $sessionYear],
                [
                    'name' => $c['name'],
                    'is_active' => true,
                    'is_published' => false,
                    'application_opens_at' => now()->startOfYear(),
                    'application_deadline_at' => now()->endOfYear(),
                ]
            );
        }
    }
}
