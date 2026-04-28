<?php

namespace App\Services\Channels;

/**
 * Simulated SMS notification channel.
 * Success rate: ~85% (simulated for testing/demo purposes)
 */
class SmsChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message, ?array $metadata = null): array
    {
        $startTime = microtime(true);

        // Simulate network latency (100ms - 500ms)
        usleep(rand(100000, 500000));

        // Simulate 85% success rate
        $success = rand(1, 100) <= 85;

        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'success' => $success,
            'response_time_ms' => $responseTimeMs,
            'channel_response' => $success
                ? "SMS delivered to {$recipient} via gateway"
                : "SMS delivery failed: Gateway timeout for {$recipient}",
        ];
    }

    public function getName(): string
    {
        return 'sms';
    }
}
