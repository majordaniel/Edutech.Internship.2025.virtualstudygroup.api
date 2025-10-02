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
        $courses = [
            [
                'course_name' => 'Introduction to Computer Science',
                'course_code' => 'CSE111',
                'course_description' => 'Overview of computing, problem-solving, and programming fundamentals.',
                'credit_units' => 3,
                'semester' => 1,
                'level' => 100,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Programming with Python',
                'course_code' => 'CSE112',
                'course_description' => 'Fundamentals of programming using Python, covering syntax, functions, and data structures.',
                'credit_units' => 2,
                'semester' => 2,
                'level' => 100,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Data Structures and Algorithms',
                'course_code' => 'CSE211',
                'course_description' => 'Covers stacks, queues, trees, graphs, and algorithm analysis techniques.',
                'credit_units' => 3,
                'semester' => 1,
                'level' => 200,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Database Management Systems',
                'course_code' => 'CSE321',
                'course_description' => 'Covers relational databases, SQL, normalization, and database design.',
                'credit_units' => 3,
                'semester' => 2,
                'level' => 300,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Artificial Intelligence',
                'course_code' => 'CSE411',
                'course_description' => 'Principles of AI including search, reasoning, and machine learning basics.',
                'credit_units' => 3,
                'semester' => 1,
                'level' => 400,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Software Engineering',
                'course_code' => 'CSE421',
                'course_description' => 'Principles of software development lifecycle, methodologies, and project management.',
                'credit_units' => 3,
                'semester' => 2,
                'level' => 400,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Computer Networks',
                'course_code' => 'CSE331',
                'course_description' => 'Introduction to networking concepts, protocols, and internet technologies.',
                'credit_units' => 3,
                'semester' => 1,
                'level' => 300,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Operating Systems',
                'course_code' => 'CSE312',
                'course_description' => 'Covers processes, threads, memory management, and file systems.',
                'credit_units' => 3,
                'semester' => 2,
                'level' => 300,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Computer Security',
                'course_code' => 'CSE422',
                'course_description' => 'Introduction to cybersecurity principles, cryptography, and secure system design.',
                'credit_units' => 3,
                'semester' => 1,
                'level' => 400,
                'department' => 'Computer Science',
            ],
            [
                'course_name' => 'Final Year Project',
                'course_code' => 'CSE499',
                'course_description' => 'Independent project under supervision, applying knowledge to solve real-world problems.',
                'credit_units' => 6,
                'semester' => 2,
                'level' => 500,
                'department' => 'Computer Science',
            ],
        ];

        return $this->faker->randomElement($courses);
    }
}
