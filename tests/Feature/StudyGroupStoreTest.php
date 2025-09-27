<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\courses;
use App\Models\students;
use App\Models\study_groups;
use App\Models\group_members_table;

class StudyGroupStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_group_and_adds_creator_as_member()
    {
        // Create prerequisite course and student
        $course = courses::create([
            'course_code' => 'CSC101',
            'course_title' => 'Intro to CS',
            'department_id' => 'CS',
            'level' => '100'
        ]);

        $student = students::create([
            'student_id' => 'S001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'date_of_birth' => '2000-01-01',
            'gender' => 'M',
            'email' => 'test@student.local',
            'phone_number' => '1234567890',
            'department_id' => 'CS',
            'matric_number' => 'MAT001',
            'status' => 'active'
        ]);

        $payload = [
            'group_name' => 'Study Group A',
            'course_id' => $course->id,
            'created_by' => $student->id,
            'description' => 'A test group'
        ];

        $response = $this->postJson('/api/study-groups', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('study_groups', ['group_name' => 'Study Group A']);
        $this->assertDatabaseHas('group_members_table', ['student_id' => $student->id, 'role' => 'Leader']);
    }
}
