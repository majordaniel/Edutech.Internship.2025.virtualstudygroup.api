<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\courses;

class courseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::create([
            'course_id' => 'ecn001',
            'course_name' => 'Economics 101',
            'course_code' => 'ecn001'
            'Credit_units' => 2
            'semester' => 1
            'level' => 100
            'department' => 'cst'
            'course_description' => 'Basic economics for beginners'
        ]);


    }
}
