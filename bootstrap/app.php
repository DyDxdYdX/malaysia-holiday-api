<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $throwable): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (Throwable $throwable, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($throwable instanceof ValidationException) {
                return response()->json([
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'details' => $throwable->errors(),
                    ],
                    'errors' => $throwable->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($throwable instanceof AuthenticationException) {
                return response()->json([
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Authentication is required.',
                        'details' => null,
                    ],
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($throwable instanceof AuthorizationException) {
                return response()->json([
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => 'You are not allowed to perform this action.',
                        'details' => null,
                    ],
                ], Response::HTTP_FORBIDDEN);
            }

            if ($throwable instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'The requested resource was not found.',
                        'details' => null,
                    ],
                ], Response::HTTP_NOT_FOUND);
            }

            if ($throwable instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'error' => [
                        'code' => 'METHOD_NOT_ALLOWED',
                        'message' => 'The method is not allowed for this endpoint.',
                        'details' => null,
                    ],
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }

            if ($throwable instanceof HttpExceptionInterface) {
                return response()->json([
                    'error' => [
                        'code' => $throwable->getStatusCode() === 429 ? 'TOO_MANY_REQUESTS' : 'HTTP_ERROR',
                        'message' => $throwable->getStatusCode() === 429
                            ? 'Too many requests.'
                            : ($throwable->getMessage() !== '' ? $throwable->getMessage() : 'HTTP error occurred.'),
                        'details' => null,
                    ],
                ], $throwable->getStatusCode());
            }

            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'An unexpected server error occurred.',
                    'details' => null,
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
