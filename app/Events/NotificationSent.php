<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a notification is successfully sent.
 */
class NotificationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public int $responseTimeMs,
        public string $channelResponse,
    ) {}
}
