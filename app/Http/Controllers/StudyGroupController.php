<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use Illuminate\Support\Str;
use App\Models\User as User;
use Illuminate\Http\Request;
use App\Models\courses as courses;
use Illuminate\Support\Facades\DB;
use App\Models\group_meetings_table;
use App\Models\StudyGroupJoinRequest;
use App\Models\study_groups as StudyGroup;
use Illuminate\Notifications\Notification;
use App\Notifications\JoinRequestNotification;
use App\Models\group_members_table as GroupMember;
use Illuminate\Notifications\DatabaseNotification;
use App\Notifications\JoinRequestStatusNotification;

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
        $request->validate([
            'group_name' => 'required|string|max:255',
            'course_id'  => 'required|exists:courses,id',
            'description' => 'required|string',
            'members'    => 'array',   // optional array of members
            'members.*'  => 'exists:users,id'
        ]);

        $group = null;

        DB::transaction(function () use ($request, &$group) {
            $userId = auth()->id();

            // fetch course
            $course = Courses::findOrFail($request->course_id);

            // prepend course code to group name
            $groupName = $course->course_code . ' - ' . $request->group_name;

            // create or get existing group
            $group = StudyGroup::firstOrCreate([
                'group_name' => $groupName,
                'course_id'  => $request->course_id,
                'created_by' => $userId,
            ], [
                'description' => $request->description,
            ]);

            // dd($group->id);
            GroupMember::firstOrCreate([
                'study_group_id' => $group->id,  // required foreign key
                'student_id'     => $userId,
            ], [
                'course_code'    => $course->course_code,
                'role'           => 'Leader',
            ]);
            $group->load('members.user');

            if ($request->has('members')) {
                foreach ($request->members as $memberId) {
                    GroupMember::firstOrCreate([
                        'study_group_id' => $group->id,
                        'student_id'     => $memberId,
                    ], [
                        'course_code'    => $course->course_code,
                        'role'           => 'Member',
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Study group created successfully',
            'group'   => $group ? $group->load('members.user') : null, // eager load members with user info
        ], 201);
    }


    //function to get the groups of a user
    public function getUserGroups()
    {
        $userId = auth()->id();

        // Fetch groups where the user is a member
        // $groups = GroupMember::where('student_id', $userId)->get();
        $groups = GroupMember::with('studyGroup')->where('student_id', $userId)->get()->map(function ($member) {
            return $member->studyGroup;
        });

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
            'group_name' => $group->group_name,
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        $admin = User::find($group->created_by);
        $admin->notify(new JoinRequestNotification($joinRequest));

        return $this->createdResponse($joinRequest, 'Join request submitted successfully');
    }

    public function handleJoinRequest(Request $request, $requestId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $joinRequest = StudyGroupJoinRequest::find($requestId);
        // $notification = DB::table('notifications');

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
                'study_group_id' => $group->id,
                'student_id' => $joinRequest->user_id,
            ], [
                'course_code' => $course?->course_code ?? null,
                'role' => 'Member',
            ]);
            $joinRequest->status = 'approved';
            $notification = DatabaseNotification::where('data->request_id', $joinRequest->id)->first();

            if ($notification) {
                $data = $notification->data;  
                $data['status'] = 'approved';

                $notification->data = $data;  
                $notification->save();
            }
            $joinRequest->save();

            $joinRequest->user->notify(new JoinRequestStatusNotification($joinRequest, 'approved', $group->group_name));

            return $this->successResponse($joinRequest, 'Join request accepted');
        } else {
            $joinRequest->status = 'rejected';
            $joinRequest->save();

            return $this->successResponse($joinRequest, 'Join request rejected');
        }
    }

    public function groupDetails($groupId)
    {
        $group = StudyGroup::with('members.user')->find($groupId);
        if (!$group) {
            return $this->notFoundResponse('Study group not found');
        }

        return $this->successResponse($group, 'Study group details fetched successfully');
    }
}
