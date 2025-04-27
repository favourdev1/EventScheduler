<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
                'errors' => null
            ], 401);
        }

        abort(401);
    }

    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
