<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Return a success response with data.
     */
    protected function success(
        JsonResource|ResourceCollection|array|null $data = null,
        string $message = 'Success',
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
                return $data->additional($response)->response()->setStatusCode($statusCode);
            }
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response.
     */
    protected function created(
        JsonResource|array|null $data = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a no content response.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return an error response.
     */
    protected function error(
        string $message = 'Error',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a validation error response.
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}
