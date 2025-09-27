<?php

namespace App\Http\Controllers;

use App\Models\User as User;
use App\Models\courses as courses;
use App\Models\study_groups as StudyGroup;
use App\Models\group_members_table as GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudyGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return StudyGroup::with('members')->get();
    }

    /**
     * Store a newly created resource in storage.
     */

    //function to get courses for the rop down
    public function getcourses()
    {
        $courses = courses::all();
        return response()->json($courses);
    }

    //search for participants
    public function searchParticipants(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        $query = $request->input('query');

        if (!$query) {
            return response()->json([]);
        }

        $students = User::where('first_name', 'like', "%{$query}%")
        ->orWhere('last_name', 'like', "%{$query}%")
        ->orWhere('email', 'like', "%{$query}%")
        ->get();

        return response()->json($students);
    }

    // Add a student to a group
    public function addMember(Request $request, $groupId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $member = GroupMember::create([
            'group_id' => $groupId,
            'student_id' => $request->student_id,
        ]);

        return response()->json([
            'message' => 'Student added to group',
            'member' => $member
        ], 201);
    }

    //function to create groups and ass members
    public function store(Request $request)
    {
        //validating the values
        $request->validate([
            'group_name' => 'required|string|max:255',

            'course_id' => 'required|exists:courses,id',

            // user_id should refer to the users table (auth users)
            'user_id'   => 'required|exists:users,id',

            'description' => 'nullable|string',
        ]);

        //creating the group id
        $group_id = Str::upper(Str::random(6));

        //creating the group
        $group = StudyGroup::create([
            'group_id' => $group_id,
            'group_name' => $request->group_name,
            'course_id' => $request->course_id,
            'created_by' => $request->user_id,
            'description' => $request->description,
            'group_name' => $request->group_name,
        ]);

        // Add creator as leader
        GroupMember::create([
            'group_id' => $group_id,
            // creator is the authenticated user id passed as user_id
            'student_id' => $request->user_id,
            'course_id' => $request->course_id,
            'role' => 'Leader',
        ]);

         // 3. Add other members from the array (if provided)
        if ($request->has('members')) {
            foreach ($request->members as $memberId) {
                GroupMember::create([
                    'group_id'   => $group_id,
                    'student_id' => $memberId,
                    'course_id' => $request->course_id,
                    'role'       => 'Member',
                ]);
            }
        }

        return response()->json([
            'message' => 'Study group created successfully',
            'group'   => $group->load('members'),
        ], 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
