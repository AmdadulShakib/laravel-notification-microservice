<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles analytics and AI training data endpoints.
 */
class AnalyticsController extends BaseApiController
{
    public function __construct(
        protected AnalyticsService $analyticsService,
    ) {}

    /**
     * GET /api/v1/analytics/training-data
     *
     * Provide structured training data for AI model consumption.
     * Supports filtering by type, status, date_from, date_to.
     */
    public function trainingData(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'status', 'date_from', 'date_to']);
        $perPage = (int) $request->get('per_page', 50);

        $trainingData = $this->analyticsService->getTrainingData($filters, $perPage);

        return $this->paginatedResponse($trainingData, 'Training data retrieved successfully.');
    }

    /**
     * GET /api/v1/dashboard/stats
     *
     * Get dashboard statistics for monitoring and overview.
     */
    public function dashboardStats(): JsonResponse
    {
        $stats = $this->analyticsService->getDashboardStats();

        return $this->successResponse($stats, 'Dashboard statistics retrieved successfully.');
    }
}
