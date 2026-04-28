<?php

namespace App\Services\Channels;

/**
 * Simulated Email notification channel.
 * Success rate: ~90% (simulated for testing/demo purposes)
 */
class EmailChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message, ?array $metadata = null): array
    {
        $startTime = microtime(true);

        // Simulate network latency (50ms - 300ms) — Email is typically faster
        usleep(rand(50000, 300000));

        // Simulate 90% success rate
        $success = rand(1, 100) <= 90;

        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

        return [
            'success' => $success,
            'response_time_ms' => $responseTimeMs,
            'channel_response' => $success
                ? "Email delivered to {$recipient} via SMTP relay"
                : "Email delivery failed: SMTP connection refused for {$recipient}",
        ];
    }

    public function getName(): string
    {
        return 'email';
    }
}
