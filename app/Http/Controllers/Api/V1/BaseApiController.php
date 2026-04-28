<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

/**
 * Base API controller with common response helpers.
 * Standardizes API response format across all endpoints.
 */
class BaseApiController extends Controller
{
    /**
     * Return a success response.
     */
    protected function successResponse(mixed $data, string $message = 'Success', int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message, int $statusCode = 400, ?array $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated response.
     */
    protected function paginatedResponse(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, string $message = 'Success'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
