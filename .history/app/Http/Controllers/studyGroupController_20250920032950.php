<?php

namespace App\Http\Controllers;

use App\Models\User as User;
use App\Models\courses as courses;
use App\Models\study_groups as StudyGroup;
use App\Models\group_members_table as GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StudyGroupController extends Controller
{
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


    //function to create groups and ass members
    public function store(Request $request)
    {
        //validating the values
        $request->validate([
            'group_name' => 'required|string|max:255',

            'course_id' => 'required|exists:courses,id',

            'user_id'   => 'required|exists:users,id',

            'description' => 'nullable|string',
        ]);

        // use DB transaction and firstOrCreate to make operation idempotent
        $group = null;
        $group_id = null;

        DB::transaction(function () use ($request, &$group, &$group_id) {
            // generate a group id only when creating
            $group_id = Str::upper(Str::random(6));

            // create or get existing group by name+course+creator to avoid duplicates
            $group = StudyGroup::firstOrCreate([
                'group_name' => $request->group_name,
                'course_id' => $request->course_id,
                'created_by' => $request->user_id,
            ], [
                'group_id' => $group_id,
                'description' => $request->description,
            ]);

            // resolve course_code from the course
            $course = courses::find($request->course_id);

            // Add creator as leader (firstOrCreate prevents duplicate member rows)
            GroupMember::firstOrCreate([
                'group_id' => $group->group_id,
                'student_id' => $request->user_id,
            ], [
                'course_code' => $course?->course_code ?? null,
                'role' => 'Leader',
            ]);

            // Add other members if provided (firstOrCreate for each)
            if ($request->has('members')) {
                foreach ($request->members as $memberId) {
                    GroupMember::firstOrCreate([
                        'group_id'   => $group->group_id,
                        'student_id' => $memberId,
                    ], [
                        'course_code' => $course?->course_code ?? null,
                        'role'       => 'Member',
                    ]);
                }
            }
        });

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
