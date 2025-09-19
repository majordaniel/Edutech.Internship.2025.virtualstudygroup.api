<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use App\Models\GroupMember;
use Illuminate\Http\Request;

class StudyGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
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
