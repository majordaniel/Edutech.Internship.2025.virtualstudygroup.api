<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use \App\ApiResponse;
    public function register(Request $request)
    {
        // Validate the request data
        $validatedData  = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validatedData->fails()) {
            return $this->validationErrorResponse($validatedData->errors(), "Validation failed");
        }
        // Check if the email already exists
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            return response()->json(['message' => 'Email already exists'], 422);
        }
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('Personal Access Token')->plainTextToken;
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        if ($validatedData->fails()) {
            return $this->validationErrorResponse($validatedData->errors(), "Validation failed");
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || !password_verify($request->password, $user->password)) {
            return $this->unauthorizedResponse('Invalid credentials');
        }
        $token = $user->createToken('Personal Access Token')->plainTextToken;
        return $this->successResponse([
            'message' => 'User logged in successfully',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->successResponse(null, 'User logged out successfully');
    }

    public function forgotPassword(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validatedData->fails()) {
            return $this->validationErrorResponse($validatedData->errors(), "Validation failed");
        }

        $user = DB::table('users')->where('email', $request->email)->first();
        $firstname = $user ? $user->first_name : '';
        $token = Str::random(6);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        Mail::to($request->email)->send(new PasswordResetMail($token, $request->email, $firstname));
        $token = Str::random(60);  

        return $this->successResponse(null, 'Password reset link sent to your email.');
    }
}
