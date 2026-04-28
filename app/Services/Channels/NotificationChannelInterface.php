<?php

namespace App\Services\Channels;

/**
 * Contract for notification delivery channels.
 * Open/Closed Principle: Add new channels by implementing this interface.
 */
interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel.
     *
     * @param string $recipient The recipient address (phone, email, etc.)
     * @param string $message The message content
     * @param array|null $metadata Additional campaign/context data
     * @return array{success: bool, response_time_ms: int, channel_response: string}
     */
    public function send(string $recipient, string $message, ?array $metadata = null): array;

    /**
     * Get the channel name identifier.
     */
    public function getName(): string;
}
