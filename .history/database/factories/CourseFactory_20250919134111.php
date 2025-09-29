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
            'Credit_units' => $this->faker->randomFloat(0, 1, 5),
            'semester' => $this->faker->numberBetween(1, 2),
            'level' => $this->faker->numberBetween(100, 500),
            'department' => $this->faker->word(),
        ];
    }
}
