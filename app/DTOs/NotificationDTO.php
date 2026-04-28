<?php

namespace App\DTOs;

/**
 * Data Transfer Object for incoming notification requests.
 * Encapsulates and validates notification data before processing.
 */
class NotificationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly string $recipient,
        public readonly string $message,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Create DTO from validated request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            type: $data['type'],
            recipient: $data['recipient'],
            message: $data['message'],
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convert DTO to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type,
            'recipient' => $this->recipient,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'status' => 'pending',
            'retry_count' => 0,
        ];
    }
}
