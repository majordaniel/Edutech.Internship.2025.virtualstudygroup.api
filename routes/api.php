<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudyGroupController;
use App\Http\Controllers\GroupMessageController;
use Symfony\Component\HttpKernel\Profiler\Profile;

// =====================
// AUTH ROUTES
// =====================
Route::group(['prefix' => 'auth'], function(){
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// =====================
// PROFILE ROUTES
// =====================
Route::group(['prefix' => 'profile', 'middleware' => 'auth:sanctum'], function(){
    Route::get('/', [ProfileController::class, 'getProfile']);
    Route::post('avatar', [ProfileController::class, 'updateAvatar']);
});

// =====================
// STUDY GROUP ROUTES
// =====================
Route::group(['prefix' => 'study-groups', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/create', [StudyGroupController::class, 'store']);
    Route::get('/getUserGroups', [StudyGroupController::class, 'getUserGroups']);
    Route::post('/participants/search', [StudyGroupController::class, 'searchParticipants']);
    Route::get('/getcourses', [StudyGroupController::class, 'getcourses']);
    Route::post('{id}/add-member', [StudyGroupController::class, 'addMember']);
    Route::delete('/{group}/leave', [StudyGroupController::class, 'leaveGroup']);
    Route::delete('/{group}/admin-remove-members/{user}', [StudyGroupController::class, 'removeMember']);
    Route::get('/{group}/files', [StudyGroupController::class, 'index']);
    Route::post('{groupId}/update', [StudyGroupController::class, 'updateGroupInfo']);
    Route::post('{groupId}/join-request', [StudyGroupController::class, 'requestToJoinGroup']);
    Route::post('{requestId}/handle-request', [StudyGroupController::class, 'handleJoinRequest']);
    Route::get('{groupId}/details', [StudyGroupController::class, 'groupDetails']);

    //meeting route
    Route::post('/{id}/start-session', [StudyGroupController::class, 'startSession']);
    Route::get('/{groupId}/details', [StudyGroupController::class, 'groupDetails']);
    Route::post('/{groupId}/update  ', [StudyGroupController::class, 'updateGroupInfo']);

    //route to make member admin and vice versa
    Route::post('/{groupId}/toggle-admin/{userId}', [StudyGroupController::class, 'toggleAdmin']);
    Route::post('/{groupId}/toggle-member/{userId}', [StudyGroupController::class, 'toggleMember']);
});

Route::get('/study-rooms', [StudyGroupController::class, 'getStudyRooms'])->middleware('auth:sanctum');

// =====================
// NOTIFICATIONS ROUTES
// =====================
Route::group(['prefix' => 'notifications', 'middleware' => 'auth:sanctum'], function () {
    Route::get('/', [ProfileController::class, 'getNotifications']);
    Route::get('/unread', [ProfileController::class, 'getUnreadNotifications']);
    Route::post('/{id}/mark-as-read', [ProfileController::class, 'markAsRead']);
    Route::post('/mark-all-as-read', [ProfileController::class, 'markAllAsRead']);
});

// =====================
// GROUP MESSAGES ROUTES
// =====================
Route::group(['prefix' => 'groups', 'middleware' => 'auth:sanctum'], function () {
    Route::post('{groupId}/messages', [GroupMessageController::class, 'sendMessage']);
    Route::get('{groupId}/messages', [GroupMessageController::class, 'fetchMessages']);
    Route::delete('/{groupId}/messages/{messageId}', [GroupMessageController::class, 'deleteMessage']);
    Route::post('/{groupId}/toggle-restriction', [GroupMessageController::class, 'toggleRestriction']);


    //file routes
    Route::post('/file/upload', [GroupMessageController::class, 'file_upload']);
    Route::get('/{group_id}/get_files', [GroupMessageController::class, 'getGroupFiles']);
    Route::get('/file/download/{id}', [GroupMessageController::class, 'downloadFile']);

    //voice note route
    Route::post('{groupId}/voice-note', [GroupMessageController::class, 'sendVoiceNote']);
});

Route::middleware('auth:sanctum')->get('/users', function (Request $request) {
    return User::all();
});
