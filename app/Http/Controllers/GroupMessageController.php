<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Models\GroupMessage;
use App\Models\study_groups;
use Illuminate\Http\Request;
use App\Models\files as Files;
use App\Events\GroupMessageSent;
use App\Models\GroupMessage as Chat;
use App\Events\GroupRestrictionToggled;
use Illuminate\Support\Facades\Storage;
use App\Models\group_members_table as GroupMember;

class GroupMessageController extends Controller
{
    use ApiResponse;
    public function sendMessage(Request $request, $groupId)
    {
        $group = study_groups::find($groupId);
        if (!$group) {
            return $this->notFoundResponse('Group not found');
        }
        $request->validate([
            'message' => 'nullable|string',
            // 'file' => 'nullable|file|max:2048',
        ]);

        if (!$request->message) {
            return $this->badRequestResponse('Message cannot be empty');
        }

        if ($group->is_restricted && auth()->id() !== $group->created_by) {
            return response()->json(['error' => 'Messaging is restricted to admins only'], 403);
        }
        // $filePath = null;
        // if ($request->hasFile('file')) {
        //     $filePath = $request->file('file')->store('messages', 'public');
        // }

        $user = auth()->user();
        if ($group->members()->where('student_id', $user->id)->doesntExist()) {
            return $this->forbiddenResponse('You are not a member of this group');
        }


        $message = GroupMessage::create([
            'group_id' => $groupId,
            'user_id' => auth()->id(),
            'message' => $request->message,
            // 'file' => $filePath,
        ]);

        broadcast(new GroupMessageSent($message, $groupId))->toOthers();

        return $this->successResponse($message, 'Message sent successfully');
    }

    public function fetchMessages($groupId)
    {
        $messages = GroupMessage::where('group_id', $groupId)->with('user')->latest()->get();
        return response()->json($messages);
    }

    //function to upload files
    public function file_upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'group_id' => 'required|integer',
            'message' => 'required|string|max:1000',
        ]);

        //function to check if group exists
        $group = study_groups::find($request->group_id);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        //check of student is a member of the group
        $group = GroupMember::where('study_group_id', $request->group_id)
            ->where('student_id', auth()->id())
            ->first();

        if (!$group) {
            return response()->json(['message' => 'Student is not a memeber of the group'], 404);
        }

        $user = auth()->user();

        // store file
        $path = $request->file('file')->store('uploads', 'public');

        // create file record
        $file = Files::create([
            'user_id' => $user->id,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $request->file('file')->getMimeType(),
            'size' => $request->file('file')->getSize(),
        ]);

        $message = $request->message;

        if ($message) {
            $message1 = $request->message;
        } else {
            $message1 = null;
        }

        // create chat message linked to file
        $chat = Chat::create([
            'user_id' => $user->id,
            'group_id' => $request->group_id,
            'message' => $message1,
            'file_id' => $file->id,
        ]);

        // broadcast chat in real-time (optional)
        // Ensure we pass the group id as the second argument to match the event constructor
        broadcast(new \App\Events\GroupMessageSent($chat, $request->group_id))->toOthers();

        return response()->json([
            'message' => 'File uploaded successfully',
            'chat' => $chat->load('file'),
        ], 201);
    }

    //function to get files
    public function getGroupFiles($group_id)
    {
        // Check if group exists
        $group = study_groups::find($group_id);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Get all chat messages with files in this group
        $chatsWithFiles = Chat::where('group_id', $group_id)
            ->whereNotNull('file_id')
            ->with('file', 'user') // include file and user info
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Files retrieved successfully',
            'data' => $chatsWithFiles
        ], 200);
    }

    //function to download file
    public function downloadFile($id)
    {
        //check of student is a member of the group
        $group = GroupMember::where('study_group_id', $id)
            ->where('student_id', auth()->id())
            ->first();

        $file = Files::find($id);

        if (!$file) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // Get the actual file path from storage
        $path = storage_path('app/public/' . $file->path);

        if (!file_exists($path)) {
            return response()->json(['message' => 'File missing from server'], 404);
        }

        return response()->download($path, $file->original_name);
    }

    public function toggleRestriction($groupId)
    {
        $group = study_groups::findOrFail($groupId);

        if (auth()->id() !== $group->created_by) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $group->is_restricted = ! $group->is_restricted;
        $group->save();

        // Broadcast restriction change
        broadcast(new GroupRestrictionToggled($group, $groupId))->toOthers();

        return response()->json([
            'message' => 'Group restriction updated',
            'is_restricted' => $group->is_restricted,
        ]);
    }
}
