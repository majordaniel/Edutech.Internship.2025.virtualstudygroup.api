<?php

namespace App;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success Response (200 OK)
     */
    protected function successResponse($data = null, $message = 'Success', $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ], $status);
    }

    protected function createdResponse($data = null, $message = 'Resource created'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }


    protected function badRequestResponse($message = 'Bad Request', $errors = null): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors
        ], 400);
    }


    protected function unauthorizedResponse($message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message
        ], 401);
    }


    protected function forbiddenResponse($message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message
        ], 403);
    }


    protected function notFoundResponse($message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message
        ], 404);
    }


    protected function validationErrorResponse($errors, $message = 'Validation errors'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors
        ], 422);
    }


    protected function serverErrorResponse($message = 'Internal Server Error'): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message
        ], 500);
    }
}
