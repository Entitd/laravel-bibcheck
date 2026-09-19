<?php

namespace Database\Seeders;

use App\Models\CourseRequirement;
use Illuminate\Database\Seeder;

class CourseRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            ['course_number' => 2, 'min_total_quantity' => 16, 'min_foreign_lang' => 4, 'min_current_periodicals' => 3, 'min_21st_century' => 10],
            ['course_number' => 3, 'min_total_quantity' => 21, 'min_foreign_lang' => 5, 'min_current_periodicals' => 5, 'min_21st_century' => 15],
            ['course_number' => 4, 'min_total_quantity' => 25, 'min_foreign_lang' => 6, 'min_current_periodicals' => 7, 'min_21st_century' => 21],
            ['course_number' => 5, 'min_total_quantity' => 31, 'min_foreign_lang' => 7, 'min_current_periodicals' => 7, 'min_21st_century' => 21],
            ['course_number' => 6, 'min_total_quantity' => 36, 'min_foreign_lang' => 8, 'min_current_periodicals' => 8, 'min_21st_century' => 21],
        ];

        foreach ($requirements as $req) {
            CourseRequirement::create($req);
        }
    }
}
