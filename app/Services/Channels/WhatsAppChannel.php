<?php

namespace App\Services\Channels;

/**
 * Simulated WhatsApp notification channel.
 * Success rate: ~80% (simulated for testing/demo purposes)
 */
class WhatsAppChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message, ?array $metadata = null): array
    {
        $startTime = microtime(true);

        // Simulate network latency (200ms - 800ms) — WhatsApp API is typically slower
        usleep(rand(200000, 800000));

        // Simulate 80% success rate
        $success = rand(1, 100) <= 80;

        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'success' => $success,
            'response_time_ms' => $responseTimeMs,
            'channel_response' => $success
                ? "WhatsApp message delivered to {$recipient} via Business API"
                : "WhatsApp delivery failed: Recipient {$recipient} not registered on WhatsApp",
        ];
    }

    public function getName(): string
    {
        return 'whatsapp';
    }
}
