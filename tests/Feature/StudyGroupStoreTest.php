<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\courses;
use App\Models\User;
use App\Models\study_groups;
use App\Models\group_members_table;

class StudyGroupStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_group_and_adds_creator_as_member()
    {
        // Create prerequisite course and student
        $course = courses::create([
            'course_id' => 'C-001',
            'course_name' => 'Intro to CS',
            'course_code' => 'CSC101',
            'course_description' => 'Introduction to Computer Science',
            'Credit_units' => '3',
            'semester' => '1',
            'level' => '100',
            'department' => 'CS',
        ]);

        // create a user and authenticate as that user
        $user = User::factory()->create([
            'email' => 'test@student.local',
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);

        $this->actingAs($user);

        $payload = [
            'group_name' => 'Study Group A',
            'course_id' => $course->id,
            'description' => 'A test group'
        ];

    $response = $this->postJson('/api/study-groups/create', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('study_groups', ['group_name' => 'Study Group A']);
    $this->assertDatabaseHas('group_members_tables', ['student_id' => (string)$user->id, 'role' => 'Leader']);
    }
}
