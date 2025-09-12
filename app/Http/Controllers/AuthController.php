<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
}
