<?php

namespace Database\Factories;

use App\Models\courses;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\courses>
 */
class CourseFactory extends Factory
{
    protected $model = courses::class;

    public function definition(): array
    {
        return [
            'course_name' => $this->faker->word(),
            'course_code' => strtoupper($this->faker->bothify('CSE###')),
            'course_description' => $this->faker->sentence(),
            'course_id' => strtoupper($this->faker->bothify('CSE###')),
            'Credit_units' => $this->faker->randomElement([1, 2]),
            'semester' => $this->faker->randomElement([1, 2]),
            'level' => $this->faker->randomElement([100, 200, 300, 400, 500]),
            'department' => $this->faker->word(),
        ];
    }
}
