<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Mail\NewUserRegisteredAdminNotification;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => ['sometimes', 'string', 'in:organizer,user']
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'user'
            ]);

            // Send welcome email to new user
            Mail::to($user)->queue(new WelcomeEmail($user));

            // Notify all admins about the new registration
            User::admins()->each(function ($admin) use ($user) {
                Mail::to($admin)->queue(new NewUserRegisteredAdminNotification($user));
            });

            $token = $user->createToken('auth_token')->plainTextToken;

            return ApiResponse::successCreated([
                'user' => $user,
                'token' => $token
            ], 'Registration successful');

        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage());
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return ApiResponse::error(
                    ['email' => ['The provided credentials are incorrect.']],
                    'Authentication failed'
                );
            }

            if (!$user->is_active) {
                return ApiResponse::error(
                    ['email' => ['This account has been deactivated.']],
                    'Account inactive'
                );
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return ApiResponse::success([
                'user' => $user,
                'token' => $token
            ], 'Login successful');

        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error(null, $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ApiResponse::success(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Logout failed');
        }
    }

    public function me(Request $request)
    {
        try {
            return ApiResponse::success($request->user(), 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve user profile');
        }
    }
}
