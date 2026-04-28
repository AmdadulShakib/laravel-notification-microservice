<?php

namespace App\Listeners;

use App\Events\NotificationFailed;
use App\Repositories\Interfaces\NotificationLogRepositoryInterface;
use App\Services\Kafka\KafkaProducerService;

/**
 * Listens for NotificationFailed events and creates AI training logs + Kafka events.
 */
class LogNotificationFailed
{
    public function __construct(
        protected NotificationLogRepositoryInterface $logRepository,
        protected KafkaProducerService $kafkaProducer,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(NotificationFailed $event): void
    {
        // Create structured AI training log with failure details
        $this->logRepository->create([
            'notification_id' => $event->notification->id,
            'type' => $event->notification->type,
            'status' => 'failed',
            'retry_count' => $event->notification->retry_count,
            'response_time_ms' => null,
            'sent_at' => null,
            'metadata' => $event->notification->metadata,
            'failure_reason' => $event->failureReason,
            'channel_response' => $event->channelResponse,
        ]);

        // Publish Kafka event
        $this->kafkaProducer->notificationFailed(
            $event->notification->id,
            $event->notification->type,
            $event->failureReason,
        );
    }
}
