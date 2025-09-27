<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudyGroup;
use App\Models\GroupMember;

use study_groups;

class studyGroupController extends Controller
{
    public function create_group(Request $request)
    {
        // Validate the request data
        $request->validate([
            'group_name' => 'required|string|max:255',
            'description' => 'required|string|ma:255',
            'course_id' => 'required|string|ma:255',
            'description' => 'nullable|string|max:255',
        ]);

        // Create a new study group
        $studyGroup = new \App\Models\StudyGroup();

        $studyGroup->group_id = $request->input('group_id');

        $studyGroup->group_name = $request->input('group_name');

        $studyGroup->course_id = $request->input('course_id');

        $studyGroup->description = $request->input('description');

        $studyGroup->created_by = auth()->id(); // Assuming user is authenticated

        $studyGroup->save();

        $group = StudyGroup::create($request->all());

        // Add creator as leader
        GroupMember::create([
            'group_id' => $group->id,
            'student_id' => $request->created_by,
            'role' => 'Leader',
        ]);


        return response()->json([
            'message' => 'Study group created successfully',
            'group' => $studyGroup], 201);
    }
}
