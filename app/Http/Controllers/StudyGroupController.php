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
use App\Notifications\JoinRequestNotification;
use App\Models\group_members_table as GroupMember;
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

    // Add member(s) to a study group by group_id
    public function addMember(Request $request, $id)
    {
        // Validate request: make sure the student exists in users table
        $request->validate([
            'student_id' => 'required|integer|exists:users,id',
        ]);

        // Try to find group by numeric primary key first, then fall back to group_id string
        $group = null;
        if (is_numeric($id)) {
            $group = StudyGroup::find($id);
        }

        if (!$group) {
            $group = StudyGroup::where('group_id', $id)->first();
        }

        if (!$group) {
            return response()->json(['message' => 'Study group not found'], 404);
        }

        // Resolve course code if available
        $course = courses::find($group->course_id);

        // Add student to group using GroupMember model to avoid using sync on hasMany
        $member = GroupMember::firstOrCreate([
            'group_id' => $group->group_id,
            'student_id' => $request->student_id,
        ], [
            'course_code' => $course?->course_code ?? null,
            'role' => 'Member',
        ]);

        // Prepare response arrays expected by tests
        $requested = [$request->student_id];
        $added = [$member->student_id ?? $request->student_id];

        return response()->json([
            'message' => 'Member added successfully',
            'group' => $group->load('members')
        ], 200);
    }

    // Function to leave a study group
    public function leaveGroup(Request $request, $groupIdentifier)
    {
        $userId = (string) $request->user()->id;

        // Resolve StudyGroup by numeric id or by group_id string
        $group = null;
        if (is_numeric($groupIdentifier)) {
            $group = StudyGroup::find($groupIdentifier);
        }

        if (!$group) {
            $group = StudyGroup::where('group_id', $groupIdentifier)->first();
        }

        if (!$group) {
            return response()->json(['message' => 'Study group not found.'], 404);
        }

        // Find the membership record in the actual members table/model
        $member = GroupMember::where('group_id', $group->group_id)
            ->where('student_id', $userId)
            ->first();

        if (!$member) {
            return response()->json([
                'message' => 'You are not a member of this group.'
            ], 403);
        }

        // If the member is a Leader, ensure there is another Leader before allowing leave
        if (isset($member->role) && strtolower($member->role) === 'leader') {
            $leaderCount = GroupMember::where('group_id', $group->group_id)
                ->whereRaw("LOWER(role) = ?", ['leader'])
                ->count();

            if ($leaderCount <= 1) {
                return response()->json([
                    'message' => 'You cannot leave as the only leader. Please assign another leader first.'
                ], 403);
            }
        }

        // Delete the membership row
        $member->delete();

        return response()->json([
            'message' => 'You have left the group successfully.'
        ], 200);
    }

    // function for admin to remove group member
    public function removeMember(Request $request, StudyGroup $group, User $user)
    {
        $requesterId = (string) $request->user()->id;

        // Ensure we have the group's string id value to query the members table
        $groupKey = $group->group_id;

        // Check if the requester is a Leader (admin) in this group
        $isAdmin = GroupMember::where('group_id', $groupKey)
            ->where('student_id', $requesterId)
            ->whereRaw("LOWER(role) = ?", ['leader'])
            ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find the target membership row
        $targetMember = GroupMember::where('group_id', $groupKey)
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
}
