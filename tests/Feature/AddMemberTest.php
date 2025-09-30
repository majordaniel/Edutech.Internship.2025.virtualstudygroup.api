<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\courses;
use App\Models\User;

class AddMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_member_to_group()
    {
        // create course
        $course = courses::create([
            'course_id' => 'C-001',
            'course_name' => 'Intro to CS',
            'course_code' => 'CSC101',
            'course_description' => 'Intro',
            'Credit_units' => '3',
            'semester' => '1',
            'level' => '100',
            'department' => 'CS',
        ]);

        // create creator and member
        $creator = User::factory()->create();
        $member = User::factory()->create();

        // authenticate as creator and create a group
        $this->actingAs($creator);

        $payload = [
            'group_name' => 'Test Group',
            'course_id' => $course->id,
            'description' => 'desc',
        ];

        $createResp = $this->postJson('/api/study-groups/create', $payload);
        $createResp->assertStatus(201);
        $group = $createResp->json('group');
        $this->assertNotEmpty($group);

        // call add-member endpoint with student_id
        $addResp = $this->postJson('/api/study-groups/' . $group['id'] . '/add-member', ['student_id' => $member->id]);
        $addResp->assertStatus(200);

        $body = $addResp->json();
        $this->assertEquals([$member->id], $body['requested']);
        $this->assertEquals([$member->id], $body['added']);

        // assert DB has the group member row (group_id is string code stored on the group)
        $this->assertDatabaseHas('group_members_tables', [
            'student_id' => (string)$member->id,
            'group_id' => $group['group_id'],
        ]);
    }
}
