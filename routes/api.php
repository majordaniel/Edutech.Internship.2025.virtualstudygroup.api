<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Symfony\Component\HttpKernel\Profiler\Profile;
use App\Http\Controllers\StudyGroupController;

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
});
