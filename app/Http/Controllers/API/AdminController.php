<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        try {
            $users = User::query()
                ->when($request->search, function($query, $search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                })
                ->when($request->role, function($query, $role) {
                    $query->where('role', $role);
                })
                ->withCount(['organizedEvents', 'registrations'])
                ->latest()
                ->paginate(15);

            return ApiResponse::success($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve users');
        }
    }

    public function createUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', Password::defaults()],
                'role' => ['required', 'string', 'in:admin,organizer,user'],
                'is_active' => ['boolean']
            ]);

            $user = User::create([
                ...$validated,
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true
            ]);

            return ApiResponse::successCreated($user, 'User created successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to create user');
        }
    }

    public function updateUser(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'password' => ['sometimes', 'required', Password::defaults()],
                'role' => ['sometimes', 'required', 'string', 'in:admin,organizer,user'],
                'is_active' => ['sometimes', 'required', 'boolean']
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return ApiResponse::success($user, 'User updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to update user');
        }
    }

    public function deleteUser(User $user)
    {
        try {
            if ($user->isAdmin() && User::admins()->count() <= 1) {
                return ApiResponse::error(null, 'Cannot delete the last admin user');
            }

            $user->delete();
            return ApiResponse::success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to delete user');
        }
    }

    public function toggleUserActive(User $user)
    {
        try {
            if ($user->isAdmin() && User::admins()->count() <= 1) {
                return ApiResponse::error(null, 'Cannot deactivate the last admin user');
            }

            $user->update(['is_active' => !$user->is_active]);
            return ApiResponse::success($user, 'User status updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to update user status');
        }
    }

    public function forceRegisterUser(Event $event, Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => ['required', 'exists:users,id']
            ]);

            DB::beginTransaction();

            // Allow registration even if event is full or user has conflicts
            $registration = $event->registrations()->create([
                'user_id' => $validated['user_id'],
                'status' => 'registered'
            ]);

            DB::commit();

            return ApiResponse::successCreated($registration, 'User registered successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error(null, 'Failed to register user');
        }
    }

    public function removeParticipant(Event $event, Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => ['required', 'exists:users,id'],
                'reason' => ['required', 'string']
            ]);

            $registration = $event->registrations()
                ->where('user_id', $validated['user_id'])
                ->firstOrFail();

            $registration->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason']
            ]);

            return ApiResponse::success(null, 'Participant removed successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to remove participant');
        }
    }

    public function statistics()
    {
        try {
            $now = Carbon::now();
            $monthStart = $now->copy()->startOfMonth();
            $yearStart = $now->copy()->startOfYear();

            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'by_role' => User::select('role', DB::raw('count(*) as count'))
                        ->groupBy('role')
                        ->pluck('count', 'role')
                ],
                'events' => [
                    'total' => Event::count(),
                    'upcoming' => Event::where('status', 'upcoming')->count(),
                    'ongoing' => Event::where('status', 'ongoing')->count(),
                    'this_month' => Event::whereBetween('start_time', [$monthStart, $now])->count(),
                    'this_year' => Event::whereBetween('start_time', [$yearStart, $now])->count(),
                ],
                'registrations' => [
                    'total' => EventRegistration::count(),
                    'active' => EventRegistration::where('status', 'registered')->count(),
                    'cancelled' => EventRegistration::where('status', 'cancelled')->count(),
                    'this_month' => EventRegistration::whereBetween('created_at', [$monthStart, $now])->count(),
                ]
            ];

            return ApiResponse::success($stats, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve statistics');
        }
    }
}
