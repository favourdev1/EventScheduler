<?php

namespace App\Http\Responses;

class ApiResponse
{
    public static function success($data = null, string $message = 'Operation successful', int $statusCode = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error($errors = null, string $message = 'Operation failed', int $statusCode = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    public static function successCreated($data = null, string $message = 'Resource created successfully')
    {
        return self::success($data, $message, 201);
    }

    public static function validationError($errors, string $message = 'Validation failed')
    {
        return self::error($errors, $message, 422);
    }

    public static function notFound(string $message = 'Resource not found')
    {
        return self::error(null, $message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized access')
    {
        return self::error(null, $message, 403);
    }

    public static function unauthenticated(string $message = 'Unauthenticated.')
    {
        return self::error(null, $message, 401);
    }
}
