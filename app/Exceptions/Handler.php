<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {


        $this->renderable(function (Throwable $e, $request) {
            // dd('Exception caught in register:', get_class($e), $e->getMessage());
            if ($request->is('api/*') || $request->expectsJson()) {
                if ($e instanceof ValidationException) {
                    return ApiResponse::validationError($e->errors());
                }

                if ($e instanceof AuthenticationException) {
                    return ApiResponse::error(null, 'Unauthenticated.', 401);
                }

                if ($e instanceof NotFoundHttpException) {
                    return ApiResponse::notFound('Resource not found');
                }

                if (config('app.debug')) {
                    return ApiResponse::error(
                        [
                            'message' => $e->getMessage(),
                            'exception' => get_class($e),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTrace()
                        ],
                        'Server Error',
                        500
                    );
                }

                return ApiResponse::error(null, 'Server Error', 500);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        dd('Exception caught:', get_class($e), $e->getMessage());

        if ($request->expectsJson() || $request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return ApiResponse::validationError($e->errors());
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(null, 'Unauthenticated.', 401);
            }

            if ($e instanceof NotFoundHttpException) {
                return ApiResponse::notFound('Resource not found');
            }

            if (config('app.debug')) {
                return ApiResponse::error(
                    [
                        'message' => $e->getMessage(),
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace()
                    ],
                    'Server Error',
                    500
                );
            }

            return ApiResponse::error(null, 'Server Error', 500);
        }

        return parent::render($request, $e);
    }

    // protected function unauthenticated($request, AuthenticationException $exception)
    // {
    //     return $request->expectsJson()
    //         ? ApiResponse::error(null, 'Unauthenticated', 401)
    //         : redirect()->guest($exception->redirectTo() ?? route('login'));
    // }

    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Access Denied! Please provide a valid token.',
        ], 401);
    }
}
