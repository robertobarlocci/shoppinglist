<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        SyncConflictException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (SyncConflictException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'conflicts' => $e->getConflicts(),
                ], 409);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());

                return new JsonResponse([
                    'success' => false,
                    'message' => "{$model} nicht gefunden.",
                ], 404);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Ressource nicht gefunden.',
                ], 404);
            }
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Nicht authentifiziert.',
                ], 401);
            }
        });
    }
}
