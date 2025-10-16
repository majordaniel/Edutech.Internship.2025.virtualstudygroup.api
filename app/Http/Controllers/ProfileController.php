<?php

namespace App\Http\Controllers;

use App\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{   
    use ApiResponse;
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $user = $request->user();

        if ($user->avatar && $user->avatar !== 'avatars/avatar.jpeg') {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();
    
        return $this->successResponse(['avatar_url' => $user->avatar_url], 'Avatar updated successfully'); 
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user,
        ]);
    }

    public function getNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();
        return $this->successResponse($notifications, 'Notifications retrieved successfully');
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return $this->notFoundResponse('Notification not found');
        }

        if ($notification->read_at) {
            return $this->badRequestResponse('Notification already marked as read');
        }

        $notification->markAsRead();

        return $this->successResponse(null, 'Notification marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return $this->successResponse(null, 'All notifications marked as read');
    }
}