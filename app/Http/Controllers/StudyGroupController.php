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
use App\Models\group_meetings_table as GroupMeeting;
use App\Models\GroupMessage as GroupMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Notifications\DatabaseNotification;
use App\Notifications\JoinRequestStatusNotification;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Events\CallEnded;

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
            $notification = DatabaseNotification::where('data->request_id', $joinRequest->id)->first();
            if ($notification) {
                $data = $notification->data;
                $data['status'] = 'rejected';

                $notification->data = $data;
                $notification->save();
            }
            $joinRequest->save();
            $joinRequest->user->notify(new JoinRequestStatusNotification($joinRequest, 'rejected', $group->group_name));

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

    // Add member(s) to a study group by group_id
    public function addMember(Request $request, $id)
{
    // 1️⃣ Validate student_id
    $validated = $request->validate([
        'student_id' => 'required|integer|exists:users,id',
    ]);

    // 2️⃣ Find the group (by id or group_id string)
    $group = is_numeric($id)
        ? StudyGroup::find($id)
        : StudyGroup::where('id', $id)->first();

    if (!$group) {
        return response()->json(['message' => 'Study group not found'], 404);
    }

    // 3️⃣ Check if the authenticated user is the group leader
    $group_role = GroupMember::where('study_group_id', $group->id)
        ->where('student_id', auth()->id())
        ->whereRaw("LOWER(role) = ?", ['leader'])
        ->exists();
    if (!$group_role) {
        return response()->json(['message' => 'Only group leaders can add members'], 403);
    }

    // 4️⃣ Check if the student is already in the group
    $alreadyMember = GroupMember::where('study_group_id', $group->id)
        ->where('student_id', $validated['student_id'])
        ->exists();

    if ($alreadyMember) {
        return response()->json(['message' => 'Student is already a member of this group'], 409);
    }

    // 5️⃣ Resolve course code if available
    $course = Courses::find($group->course_id);

    // 6️⃣ Add the new member
    $member = GroupMember::create([
        'study_group_id'    => $id,
        'student_id'  => $validated['student_id'],
        'course_code' => $course?->course_code,
        'role'        => 'Member',
    ]);

    // 7️⃣ Return success response
    return response()->json([
        'message' => 'Member added successfully',
        'member'  => $member,
        'group'   => $group->load('members'),
    ], 200);
}

    // Function to leave a study group
    public function leaveGroup(Request $request, $group)
    {
        $check_group = StudyGroup::find($group);

        if(!$check_group)
        {
             return response()->json(['message' => 'Study group not found.'], 404);
        }

        $check_student = GroupMember::where('study_group_id', $group)
            ->where('student_id', auth()->id())
            ->first();

        if(!$check_student)
        {
            return response()->json(['message' => 'Student is not in study group.'], 404);
        }

        $student_role = GroupMember::where('study_group_id', $group)
                ->whereRaw("LOWER(role) = ?", ['leader'])
                ->get();

        //check if student id had leader role
        $student_role_count = GroupMember::where('study_group_id', $group)
                ->whereRaw("LOWER(role) = ?", ['leader'])
                ->count();

        if ($student_role_count > 0 && strtolower($check_student->role) === 'leader')
        {
            return response()->json([
                    'message' => 'You cannot leave as the only leader. Please assign another leader first.'
                ], 403);
        }


        // Delete the membership row
        $check_student->delete();

        return response()->json([
            'message' => 'You have left the group successfully.'
        ], 200);
    }

    // function for admin to remove group member
    public function removeMember(Request $request, StudyGroup $group, User $user)
    {
        $requesterId = (string) $request->user()->id;

        // Ensure we have the group's string id value to query the members table
        $groupKey = $group->id;

        // Check if the requester is a Leader (admin) in this group
        $isAdmin = GroupMember::where('study_group_id', $groupKey)
            ->where('student_id', $requesterId)
            ->whereRaw("LOWER(role) = ?", ['leader'])
            ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'You are not authorized to remove anyone'], 403);
        }

        // Find the target membership row
        $targetMember = GroupMember::where('study_group_id', $groupKey)
            ->where('student_id', (string)$user->id)
            ->first();

        if (!$targetMember) {
            return response()->json(['message' => 'User not in group'], 404);
        }

        // Prevent removing self (optional)
        if ($requesterId === (string)$user->id) {
            return response()->json(['message' => 'You cannot remove yourself'], 400);
        }

        // If the target is a Leader, ensure there's another Leader before removal
        if (isset($targetMember->role) && strtolower($targetMember->role) === 'leader') {
            $leaderCount = GroupMember::where('group_id', $groupKey)
                ->whereRaw("LOWER(role) = ?", ['leader'])
                ->count();

            if ($leaderCount <= 1) {
                return response()->json([
                    'message' => 'Cannot remove the only leader. Assign another leader first.'
                ], 403);
            }
        }

        // Delete the membership row
        $targetMember->delete();

        return response()->json(['message' => 'Member removed successfully']);
    }

    //function ro read files
    public function index(Request $request, StudyGroup $group)
    {
        $userId = (string)$request->user()->id;

        // Ensure the requester is a member (check membership table)
        $isMember = GroupMember::where('group_id', $group->group_id)
            ->where('student_id', $userId)
            ->exists();

        if (!$isMember) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Optional search and pagination
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);

        $query = $group->files()->with('uploadedBy')->orderBy('created_at', 'desc');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $files = $query->paginate(max(1, min(100, $perPage)));

        return response()->json($files);
    }

    public function startSession(Request $request, $id)
    {
        try {
            // Since route is protected by auth:sanctum middleware, user is already authenticated
            $userId = auth()->id();
            $user = User::find($userId);

            // Check if group exists
            $group = StudyGroup::find($id);
            if (!$group) {
                return response()->json(['message' => 'Group not found'], 404);
            }

            // Check if user is a member of the group
            $isMember = GroupMember::where('study_group_id', $id)
                ->where('student_id', $userId)
                ->exists();

            if (!$isMember) {
                return response()->json(['message' => 'User is not a member of the group'], 403);
            }

            // Generate a JWT token for Jitsi authentication
            $key = env('JWT_SECRET');
            if (!$key) {
                return response()->json(['message' => 'JWT secret not configured'], 500);
            }

            $payload = [
                'sub' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'exp' => time() + (60 * 60), // Token expires in 1 hour
                'aud' => 'jitsi', // Audience
                'iss' => config('app.name'), // Issuer
                'room' => 'group_' . $id . '_*', // Allow access to rooms with this prefix
            ];

            // Add kid header to JWT token to avoid 'kid' claim missing error
            $headers = [
                'kid' => 'default-jwt-key'
            ];
            $jitsiToken = JWT::encode($payload, $key, 'HS256', null, $headers);

            // Generate meeting info
            $roomName = 'group_' . $id . '_' . Str::uuid();
            $meetingUrl = 'https://meet.jit.si/' . $roomName;
            $now = Carbon::now();

            // Save meeting record
            $meeting = GroupMeeting::create([
                'host_id' => $userId,
                'group_id' => $id,
                'meeting_date' => $now->toDateString(),
                'meeting_time' => $now->toTimeString(),
                'meeting_link' => $meetingUrl,
            ]);

            // Save message record (linked to the call)
            $message = GroupMessage::create([
                'group_id' => $id,
                'user_id' => $userId,
                'message' => null,
                'file_id' => null,
                'call_id' => $meeting->id,
            ]);

            // Return response
            return response()->json([
                'message' => 'Meeting started successfully',
                'data' => [
                    'meeting' => $meeting,
                    'join_url' => $meetingUrl,
                    'message' => $message->load('meeting'),
                    'jitsi_token' => $jitsiToken, // JWT token for Jitsi authentication
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while starting the session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateGroupInfo(Request $request, $groupId)
    {
        $request->validate([
            'group_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
        ]);

        $group = StudyGroup::find($groupId);
        if (!$group) {
            return $this->notFoundResponse('Study group not found');
        }

        if ($group->created_by !== auth()->id()) {
            return $this->unauthorizedResponse('Only the group creator can update group info');
        }

        if ($request->has('group_name')) {
            $group->group_name = $request->input('group_name');
        }
        if ($request->has('description')) {
            $group->description = $request->input('description');
        }

        $group->save();

        return $this->successResponse($group, 'Study group info updated successfully');
    }

     // API endpoint to toggle admin role
    public function toggleAdmin(Request $request, $groupId, $userId)
    {
        $group = StudyGroup::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Study group not found'], 404);
        }

        if ($group->created_by !== auth()->id()) {
            return response()->json(['message' => 'Only the group creator can toggle admin roles'], 403);
        }

        $member = GroupMember::where('study_group_id', $groupId)
            ->where('student_id', $userId)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'User is not a member of this group'], 404);
        }

        if (strtolower($member->role) === 'member') {
            $member->role = 'Admin';
            $message = 'User promoted to Admin successfully';
        } elseif (strtolower($member->role) === 'admin') {
            return response()->json(['message' => 'User is already an admin'], 404);
        }

        $member->save();

        return response()->json([
            'message' => $message,
            'data' => $member
        ], 200);
    }

    // API endpoint to toggle admin role
    public function togglemember(Request $request, $groupId, $userId)
    {
        $group = StudyGroup::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Study group not found'], 404);
        }

        if ($group->created_by !== auth()->id()) {
            return response()->json(['message' => 'Only the group creator can toggle admin roles'], 403);
        }

        $member = GroupMember::where('study_group_id', $groupId)
            ->where('student_id', $userId)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'User is not a member of this group'], 404);
        }

        if (strtolower($member->role) === 'admin') {
            $member->role = 'Member';
            $message = 'User Demoted to adminsuccessfully';
        } elseif (strtolower($member->role) === 'member') {
            return response()->json(['message' => 'User is already a member'], 404);
        }

        $member->save();

        return response()->json([
            'message' => $message,
            'data' => $member
        ], 200);
    }


    public function startCall(Request $request, $groupId)
    {
        $group = StudyGroup::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }
        if(!GroupMember::where('study_group_id', $groupId)
            ->where('student_id', auth()->id())
            ->exists()) {
            return response()->json(['message' => 'User is not a member of the group'], 403);
        }
        // Create a new Whereby meeting via API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.whereby.api_key'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.whereby.base_url'), [
            'endDate' => now()->addHour()->toIso8601String(),
            'fields' => ['hostRoomUrl', 'roomUrl']
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Failed to create meeting', 'error' => $response->json()], 500);
        }

        $data = $response->json();
        broadcast(new \App\Events\CallStarted($data, $groupId))->toOthers();

        return response()->json([
            'message' => 'Meeting created successfully',
            'host_url' => $data['hostRoomUrl'] ?? null,
            'guest_url' => $data['roomUrl'] ?? null,
        ]);
    }

    public function endCall(Request $request, $groupId)
{
    $group = StudyGroup::find($groupId);
    if (!$group) {
        return response()->json(['message' => 'Group not found'], 404);
    }

    // Check that user is a member of the group
    $isMember = GroupMember::where('study_group_id', $groupId)
        ->where('student_id', auth()->id())
        ->exists();

    if (!$isMember) {
        return response()->json(['message' => 'User is not a member of the group'], 403);
    }

    // Broadcast event to notify everyone the call has ended
    broadcast(new \App\Events\CallEnded($groupId))->toOthers();

    return response()->json(['message' => 'Call ended successfully']);
}
}
