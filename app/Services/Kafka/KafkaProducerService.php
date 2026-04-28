<?php

namespace App\Services\Kafka;

use Illuminate\Support\Facades\Log;

/**
 * Mock Kafka Producer for event-driven architecture.
 *
 * In production, this would connect to a real Kafka broker.
 * For this microservice demo, events are logged to simulate Kafka publishing.
 *
 * Events published:
 * - notification.created
 * - notification.sent
 * - notification.failed
 */
class KafkaProducerService
{
    protected string $topic = 'notification-events';

    /**
     * Publish an event to the Kafka topic (simulated).
     */
    public function publish(string $eventType, array $data): void
    {
        $event = [
            'event_type' => $eventType,
            'topic' => $this->topic,
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ];

        // In production: $this->kafkaProducer->produce($this->topic, json_encode($event));
        // For demo: Log the event to simulate Kafka publishing
        Log::channel('kafka')->info("Kafka Event Published: {$eventType}", $event);
    }

    /**
     * Publish notification.created event.
     */
    public function notificationCreated(int $notificationId, string $type, string $recipient): void
    {
        $this->publish('notification.created', [
            'notification_id' => $notificationId,
            'type' => $type,
            'recipient' => $recipient,
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Publish notification.sent event.
     */
    public function notificationSent(int $notificationId, string $type, int $responseTimeMs): void
    {
        $this->publish('notification.sent', [
            'notification_id' => $notificationId,
            'type' => $type,
            'response_time_ms' => $responseTimeMs,
            'sent_at' => now()->toISOString(),
        ]);
    }

    /**
     * Publish notification.failed event.
     */
    public function notificationFailed(int $notificationId, string $type, string $reason): void
    {
        $this->publish('notification.failed', [
            'notification_id' => $notificationId,
            'type' => $type,
            'failure_reason' => $reason,
            'failed_at' => now()->toISOString(),
        ]);
    }
}
