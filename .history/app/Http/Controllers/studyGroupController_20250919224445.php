<?php

namespace App\Http\Controllers;

use App\Models\User as User;
use App\Models\courses as courses;
use App\Models\study_groups as StudyGroup;
use App\Models\group_members_table as GroupMember;
use Illuminate\Http\Request;

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

        $query = $request->query('q');

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

    public function store(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'created_by' => 'required|exists:students,id',
            'description' => 'nullable|string',
        ]);

        $group = StudyGroup::create($request->all());

        // Add creator as leader
        GroupMember::create([
            'group_id' => $group->id,
            'student_id' => $request->created_by,
            'role' => 'Leader',
        ]);

        return response()->json([
            'message' => 'Study group created successfully',
            'group' => $group
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
