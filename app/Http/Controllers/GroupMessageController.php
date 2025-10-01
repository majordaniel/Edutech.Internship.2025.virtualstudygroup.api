<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use App\Models\GroupMessage;
use Illuminate\Http\Request;
use App\Events\GroupMessageSent;
use App\Models\study_groups;

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

        // $filePath = null;
        // if ($request->hasFile('file')) {
        //     $filePath = $request->file('file')->store('messages', 'public');
        // }

        $user = auth()->user(); 
       
        if ($group->members()->where('student_id', $user->id)->doesntExist()) {
            return $this->forbiddenResponse('You are not a member of this group');
        }

        if($groupId != $user->study_group_id){
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
}
