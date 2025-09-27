<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $studyGroup->group_name = $request->input('group_name');
        $studyGroup->description = $request->input('description');
        $studyGroup->subject = $request->input('subject');
        $studyGroup->created_by = auth()->id(); // Assuming user is authenticated
        $studyGroup->save();

        return response()->json(['message' => 'Study group created successfully', 'group' => $studyGroup], 201);
    }
}
