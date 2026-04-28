<?php

namespace App\DTOs;

use App\Models\Notification;

/**
 * Data Transfer Object for API responses.
 * Standardizes notification response formatting.
 */
class NotificationResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $type,
        public readonly string $recipient,
        public readonly string $status,
        public readonly int $retryCount,
        public readonly ?int $responseTimeMs = null,
        public readonly ?string $sentAt,
        public readonly ?array $metadata,
        public readonly string $createdAt,
    ) {}

    /**
     * Create DTO from Notification model.
     */
    public static function fromModel(Notification $notification): self
    {
        return new self(
            id: $notification->id,
            userId: $notification->user_id,
            type: $notification->type,
            recipient: $notification->recipient,
            status: $notification->status,
            retryCount: $notification->retry_count ?? 0,
            responseTimeMs: $notification->response_time_ms,
            sentAt: $notification->sent_at?->toISOString(),
            metadata: $notification->metadata,
            createdAt: $notification->created_at->toISOString(),
        );
    }

    /**
     * Convert to array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type,
            'recipient' => $this->recipient,
            'status' => $this->status,
            'retry_count' => $this->retryCount,
            'response_time_ms' => $this->responseTimeMs,
            'sent_at' => $this->sentAt,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
        ];
    }
}
