<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a notification fails to send.
 */
class NotificationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public string $failureReason,
        public string $channelResponse,
    ) {}
}
