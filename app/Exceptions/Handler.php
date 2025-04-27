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
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                if ($e instanceof ValidationException) {
                    return ApiResponse::validationError($e->errors());
                }

                if ($e instanceof AuthenticationException) {
                    return ApiResponse::error(null, 'Unauthenticated', 401);
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
}
