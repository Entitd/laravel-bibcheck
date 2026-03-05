<?php

namespace Database\Seeders;

use App\Models\CourseRequirement;
use Illuminate\Database\Seeder;

class CourseRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $requirements = [
            ['course_number' => 2, 'min_total_quantity' => 15, 'min_foreign_lang' => 5, 'min_current_periodicals' => 4, 'min_21st_century' => 11],
            ['course_number' => 3, 'min_total_quantity' => 20, 'min_foreign_lang' => 6, 'min_current_periodicals' => 6, 'min_21st_century' => 16],
            ['course_number' => 4, 'min_total_quantity' => 25, 'min_foreign_lang' => 7, 'min_current_periodicals' => 8, 'min_21st_century' => 22],
            ['course_number' => 5, 'min_total_quantity' => 30, 'min_foreign_lang' => 8, 'min_current_periodicals' => 8, 'min_21st_century' => 22],
            ['course_number' => 6, 'min_total_quantity' => 30, 'min_foreign_lang' => 8, 'min_current_periodicals' => 8, 'min_21st_century' => 22],
        ];

        foreach ($requirements as $req) {
            CourseRequirement::create($req);
        }
    }
}
