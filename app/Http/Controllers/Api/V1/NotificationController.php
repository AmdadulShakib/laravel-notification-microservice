<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\NotificationDTO;
use App\Http\Requests\SendNotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles notification API endpoints.
 * Follows thin controller pattern — delegates to NotificationService.
 */
class NotificationController extends BaseApiController
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    /**
     * POST /api/v1/notifications/send
     *
     * Create and queue a notification for async delivery.
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $dto = NotificationDTO::fromArray($request->validated());

        $response = $this->notificationService->sendNotification($dto);

        return $this->successResponse(
            $response->toArray(),
            'Notification queued successfully.',
            201
        );
    }

    /**
     * GET /api/v1/notifications/{id}/status
     *
     * Get the current status of a notification.
     */
    public function status(int $id): JsonResponse
    {
        $response = $this->notificationService->getNotificationStatus($id);

        if (!$response) {
            return $this->errorResponse('Notification not found.', 404);
        }

        return $this->successResponse($response->toArray());
    }

    /**
     * GET /api/v1/notifications
     *
     * List notifications with optional filters.
     * Filters: status, type, user_id, date_from, date_to
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'user_id', 'date_from', 'date_to']);
        $perPage = (int) $request->get('per_page', 15);

        $notifications = $this->notificationService->getNotifications($filters, $perPage);

        return $this->paginatedResponse($notifications, 'Notifications retrieved successfully.');
    }

    /**
     * POST /api/v1/notifications/{id}/retry
     *
     * Manually retry a failed notification.
     */
    public function retry(int $id): JsonResponse
    {
        $response = $this->notificationService->retryNotification($id);

        if (!$response) {
            return $this->errorResponse(
                'Cannot retry this notification. Either not found or max retries exceeded.',
                422
            );
        }

        return $this->successResponse(
            $response->toArray(),
            'Notification retry queued successfully.'
        );
    }
}
