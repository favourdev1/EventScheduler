<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::error(null, 'Access Denied! Please provide a valid token.', Response::HTTP_UNAUTHORIZED);
            }

            // For non-API requests, you might want to redirect to a login page
            return redirect()->guest(route('login'));
        });

        // You can also register other custom exception rendering logic here
        $exceptions->render(function (\Throwable $e, $request) {
            // Custom rendering for other exceptions if needed
        });
    })->create();
