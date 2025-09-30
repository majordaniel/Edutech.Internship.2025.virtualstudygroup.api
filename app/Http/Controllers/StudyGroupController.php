<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use Illuminate\Support\Str;
use App\Models\User as User;
use Illuminate\Http\Request;
use App\Models\courses as courses;
use Illuminate\Support\Facades\DB;
use App\Models\StudyGroupJoinRequest;
use App\Models\study_groups as StudyGroup;
use App\Models\group_members_table as GroupMember;

class StudyGroupController extends Controller
{
    use ApiResponse;
    //function to get courses for the drop down
    public function getcourses()
    {
        $courses = courses::all();
        return response()->json($courses);
    }

    //search for participants
    public function searchParticipants(Request $request)
    {
        //validating the query
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        //assigning the query to a variable named query
        $query = $request->input('query');

        //checking if the query is empty
        if (!$query) {
            return response()->json([]);
        }

        //Searching for students in the database
        $students = User::where('first_name', 'like', "%{$query}%")
        ->orWhere('last_name', 'like', "%{$query}%")
        ->orWhere('email', 'like', "%{$query}%")
        ->get();

        //returning the response
        return response()->json($students);
    }

    //function to create groups and groups members
    public function store(Request $request)
    {
        //validating the values
        $request->validate([
            'group_name' => 'required|string|max:255',

            'course_id' => 'required|exists:courses,id',

            'description' => 'required|string',
        ]);

        // use DB transaction and firstOrCreate to make operation idempotent
        $group = null;
        $group_id = null;


        //setting a function to run all that is inputed in it as a database transaction
        DB::transaction(function () use ($request, &$group, &$group_id) {
            //getting the authenticated user id
            $userId = auth()->id();

            // generate a group id only when creating
            $group_id = Str::upper(Str::random(6));

            // create or get existing group by name+course+creator to avoid duplicates
            $group = StudyGroup::firstOrCreate([
                'group_name' => $request->group_name,
                'course_id' => $request->course_id,
                'created_by' => $userId,
            ], [
                'group_id' => $group_id,
                'description' => $request->description,
            ]);

            // resolve course_code from the course
            $course = courses::find($request->course_id);

            // Add creator as leader (firstOrCreate prevents duplicate member rows)
            GroupMember::firstOrCreate([
                'group_id' => $group->group_id,
                'student_id' => $userId,
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

    //function to get the groups of a user
    public function getUserGroups()
    {
        $userId = auth()->id();

        // Fetch groups where the user is a member
        $groups = GroupMember::where('student_id', $userId)->get();

        return response()->json([
            'status' => 'success',
            'data' => $groups
        ]);
    }

    public function getStudyRooms()
    {
        $userId = auth()->id();

        // Fetch all study groups
        $groups = StudyGroup::all();

        return $this->successResponse($groups, 'Study rooms fetched successfully');
    }

    public function requestToJoinGroup($groupId)
    {
        $group = StudyGroup::where('id', $groupId)->first();
        if (!$group) {
            return $this->notFoundResponse('Study group not found');
        }

        if ($group->created_by == auth()->id()) {
            return $this->badRequestResponse('You are the creator of this group');
        }
        
        // Check if request already exists
        $existing = StudyGroupJoinRequest::where('group_id', $groupId)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();  
        
        if ($existing) {
            return $this->badRequestResponse('You have already requested to join this group');             
        }

        $joinRequest = StudyGroupJoinRequest::create([
            'group_id' => $groupId,
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return $this->createdResponse($joinRequest, 'Join request submitted successfully');
    }

    public function handleJoinRequest(Request $request, $requestId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $joinRequest = StudyGroupJoinRequest::find($requestId);

        if (!$joinRequest) {
            return $this->notFoundResponse('Join request not found');
        }

        if ($joinRequest->status !== 'pending') {
            return $this->badRequestResponse('This join request has already been handled');
        }

        $group = StudyGroup::where('id', $joinRequest->group_id)->first();
        if (!$group) {
            return $this->notFoundResponse('Study group not found');
        }

        // Only group creator can handle requests
        if ($group->created_by !== auth()->id()) {
            return $this->unauthorizedResponse('Only the group creator can handle join requests');
        }

        if ($request->action === 'approve') {
            // Add user as member if not already
            $course = courses::find($group->course_id);
            GroupMember::firstOrCreate([
                'group_id' => $group->group_id,
                'student_id' => $joinRequest->user_id,
            ], [
                'course_code' => $course?->course_code ?? null,
                'role' => 'Member',
            ]);
            $joinRequest->status = 'approved';
            $joinRequest->save();

            return $this->successResponse($joinRequest, 'Join request accepted');
        } else {
            $joinRequest->status = 'rejected';
            $joinRequest->save();

            return $this->successResponse($joinRequest, 'Join request rejected');
        }
    }
}