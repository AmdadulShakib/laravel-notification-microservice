<?php

namespace App\Jobs;

use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Services\CircuitBreaker\CircuitBreakerService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Async job for sending notifications through channels.
 *
 * Features:
 * - Max 3 retry attempts with exponential backoff [10s, 30s, 90s]
 * - Circuit breaker integration
 * - AI training log generation via events
 * - Kafka event publishing via listeners
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public int $tries = 3;

    /**
     * Exponential backoff intervals in seconds.
     * 1st retry: 10s, 2nd retry: 30s, 3rd retry: 90s
     */
    public array $backoff = [10, 30, 90];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Notification $notification
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationRepositoryInterface $notificationRepository): void
    {
        $notification = $this->notification;

        Log::info("Processing notification #{$notification->id} ({$notification->type}) to {$notification->recipient}");

        // Update status to processing
        $notificationRepository->updateStatus($notification->id, 'processing');

        // Check circuit breaker for this channel
        $circuitBreaker = new CircuitBreakerService("channel_{$notification->type}");

        if (!$circuitBreaker->isAvailable()) {
            Log::warning("Circuit breaker OPEN for channel: {$notification->type}. Releasing job back to queue.");
            // Release back to queue with delay
            $this->release(30);
            return;
        }

        try {
            // Resolve the appropriate channel
            $channel = NotificationService::resolveChannel($notification->type);

            // Send notification (simulated)
            $result = $channel->send(
                $notification->recipient,
                $notification->message,
                $notification->metadata,
            );

            if ($result['success']) {
                // Mark as sent
                $notificationRepository->updateStatus($notification->id, 'sent', [
                    'response_time_ms' => $result['response_time_ms'],
                    'sent_at' => now(),
                ]);
                $notification->refresh();

                // Record success for circuit breaker
                $circuitBreaker->recordSuccess();

                // Fire success event → triggers AI log + Kafka event
                event(new NotificationSent($notification, $result['response_time_ms'], $result['channel_response']));

                Log::info("Notification #{$notification->id} sent successfully in {$result['response_time_ms']}ms");
            } else {
                // Record failure for circuit breaker
                $circuitBreaker->recordFailure();

                // Throw exception to trigger retry mechanism
                throw new \RuntimeException($result['channel_response']);
            }
        } catch (\Exception $e) {
            // Update retry count
            $notificationRepository->incrementRetryCount($notification->id);
            $notification->refresh();

            Log::warning("Notification #{$notification->id} attempt {$this->attempts()}/{$this->tries} failed: {$e->getMessage()}");

            // If this is not the last attempt, re-throw to trigger backoff retry
            if ($this->attempts() < $this->tries) {
                // Set status back to pending for retry
                $notificationRepository->updateStatus($notification->id, 'pending');
                throw $e;
            }

            // Last attempt failed — mark as permanently failed
            $notificationRepository->updateStatus($notification->id, 'failed', [
                'failed_at' => now(),
            ]);
            $notification->refresh();

            // Fire failure event → triggers AI log + Kafka event
            event(new NotificationFailed($notification, $e->getMessage(), $e->getMessage()));

            Log::error("Notification #{$notification->id} permanently failed after {$this->tries} attempts");
        }
    }

    /**
     * Handle a job failure (after all retries exhausted).
     */
    public function failed(?\Throwable $exception): void
    {
        $notificationRepository = app(NotificationRepositoryInterface::class);

        $notificationRepository->updateStatus($this->notification->id, 'failed', [
            'failed_at' => now(),
        ]);
        $this->notification->refresh();

        Log::error("SendNotificationJob permanently failed for notification #{$this->notification->id}", [
            'error' => $exception?->getMessage(),
        ]);

        // Fire failure event if not already fired
        event(new NotificationFailed(
            $this->notification,
            $exception?->getMessage() ?? 'Unknown error',
            'Job failed after all retries',
        ));
    }

    /**
     * Calculate the maximum time the job should run.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }
}
