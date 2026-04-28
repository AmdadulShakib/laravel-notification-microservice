<?php

namespace App\Services;

use App\DTOs\NotificationDTO;
use App\DTOs\NotificationResponseDTO;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Services\Channels\EmailChannel;
use App\Services\Channels\NotificationChannelInterface;
use App\Services\Channels\SmsChannel;
use App\Services\Channels\WhatsAppChannel;
use App\Services\Kafka\KafkaProducerService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Core business logic for notification processing.
 * Follows Single Responsibility Principle — only handles notification orchestration.
 */
class NotificationService
{
    public function __construct(
        protected NotificationRepositoryInterface $notificationRepository,
        protected KafkaProducerService $kafkaProducer,
    ) {}

    /**
     * Create a notification and dispatch it to the queue.
     */
    public function sendNotification(NotificationDTO $dto): NotificationResponseDTO
    {
        // Create notification record with 'pending' status
        $notification = $this->notificationRepository->create($dto->toArray());

        // Publish Kafka event: notification.created
        $this->kafkaProducer->notificationCreated(
            $notification->id,
            $notification->type,
            $notification->recipient,
        );

        // Dispatch to queue for async processing
        SendNotificationJob::dispatch($notification);

        return NotificationResponseDTO::fromModel($notification);
    }

    /**
     * Get notification status by ID.
     */
    public function getNotificationStatus(int $id): ?NotificationResponseDTO
    {
        $notification = $this->notificationRepository->findById($id);

        if (!$notification) {
            return null;
        }

        return NotificationResponseDTO::fromModel($notification);
    }

    /**
     * Get filtered notifications list.
     */
    public function getNotifications(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->notificationRepository->getFiltered($filters, $perPage);
    }

    /**
     * Retry a failed notification.
     */
    public function retryNotification(int $id): ?NotificationResponseDTO
    {
        $notification = $this->notificationRepository->findById($id);

        if (!$notification || !$notification->canRetry()) {
            return null;
        }

        // Reset status to pending
        $this->notificationRepository->updateStatus($id, 'pending');
        $notification->refresh();

        // Re-dispatch to queue
        SendNotificationJob::dispatch($notification);

        return NotificationResponseDTO::fromModel($notification);
    }

    /**
     * Resolve the appropriate notification channel based on type.
     * Open/Closed Principle: Add new channels without modifying existing code.
     */
    public static function resolveChannel(string $type): NotificationChannelInterface
    {
        return match ($type) {
            'sms' => new SmsChannel(),
            'email' => new EmailChannel(),
            'whatsapp' => new WhatsAppChannel(),
            default => throw new \InvalidArgumentException("Unsupported notification type: {$type}"),
        };
    }
}
