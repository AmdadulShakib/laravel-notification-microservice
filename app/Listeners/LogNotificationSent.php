<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use App\Repositories\Interfaces\NotificationLogRepositoryInterface;
use App\Services\Kafka\KafkaProducerService;

/**
 * Listens for NotificationSent events and creates AI training logs + Kafka events.
 */
class LogNotificationSent
{
    public function __construct(
        protected NotificationLogRepositoryInterface $logRepository,
        protected KafkaProducerService $kafkaProducer,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(NotificationSent $event): void
    {
        // Create structured AI training log
        $this->logRepository->create([
            'notification_id' => $event->notification->id,
            'type' => $event->notification->type,
            'status' => 'sent',
            'retry_count' => $event->notification->retry_count,
            'response_time_ms' => $event->responseTimeMs,
            'sent_at' => now(),
            'metadata' => $event->notification->metadata,
            'channel_response' => $event->channelResponse,
        ]);

        // Publish Kafka event
        $this->kafkaProducer->notificationSent(
            $event->notification->id,
            $event->notification->type,
            $event->responseTimeMs,
        );
    }
}
