<?php

namespace Tests\Unit;

use App\DTOs\NotificationDTO;
use App\DTOs\NotificationResponseDTO;
use App\Models\Notification;
use App\Services\Channels\EmailChannel;
use App\Services\Channels\SmsChannel;
use App\Services\Channels\WhatsAppChannel;
use App\Services\CircuitBreaker\CircuitBreakerService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for NotificationService, DTOs, Channels, and CircuitBreaker.
 */
class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: NotificationDTO creation from array.
     */
    public function test_notification_dto_from_array(): void
    {
        $data = [
            'user_id' => 1,
            'type' => 'email',
            'recipient' => 'test@example.com',
            'message' => 'Hello World',
            'metadata' => ['campaign_id' => 'camp-1'],
        ];

        $dto = NotificationDTO::fromArray($data);

        $this->assertEquals(1, $dto->userId);
        $this->assertEquals('email', $dto->type);
        $this->assertEquals('test@example.com', $dto->recipient);
        $this->assertEquals('Hello World', $dto->message);
        $this->assertEquals(['campaign_id' => 'camp-1'], $dto->metadata);
    }

    /**
     * Test: NotificationDTO toArray conversion.
     */
    public function test_notification_dto_to_array(): void
    {
        $dto = new NotificationDTO(
            userId: 5,
            type: 'sms',
            recipient: '+8801234567890',
            message: 'Test SMS',
        );

        $array = $dto->toArray();

        $this->assertEquals(5, $array['user_id']);
        $this->assertEquals('sms', $array['type']);
        $this->assertEquals('pending', $array['status']);
        $this->assertEquals(0, $array['retry_count']);
    }

    /**
     * Test: NotificationResponseDTO from model.
     */
    public function test_notification_response_dto_from_model(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'type' => 'email',
            'recipient' => 'test@example.com',
            'message' => 'Test',
            'status' => 'sent',
            'response_time_ms' => 250,
            'sent_at' => now(),
        ]);

        $dto = NotificationResponseDTO::fromModel($notification);

        $this->assertEquals($notification->id, $dto->id);
        $this->assertEquals('sent', $dto->status);
        $this->assertEquals(250, $dto->responseTimeMs);
        $this->assertNotNull($dto->sentAt);
    }

    /**
     * Test: Channel resolution returns correct channel.
     */
    public function test_resolve_sms_channel(): void
    {
        $channel = NotificationService::resolveChannel('sms');
        $this->assertInstanceOf(SmsChannel::class, $channel);
        $this->assertEquals('sms', $channel->getName());
    }

    public function test_resolve_email_channel(): void
    {
        $channel = NotificationService::resolveChannel('email');
        $this->assertInstanceOf(EmailChannel::class, $channel);
        $this->assertEquals('email', $channel->getName());
    }

    public function test_resolve_whatsapp_channel(): void
    {
        $channel = NotificationService::resolveChannel('whatsapp');
        $this->assertInstanceOf(WhatsAppChannel::class, $channel);
        $this->assertEquals('whatsapp', $channel->getName());
    }

    /**
     * Test: Invalid channel throws exception.
     */
    public function test_resolve_invalid_channel_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        NotificationService::resolveChannel('telegram');
    }

    /**
     * Test: SMS channel send returns expected structure.
     */
    public function test_sms_channel_returns_valid_response(): void
    {
        $channel = new SmsChannel();
        $result = $channel->send('+8801234567890', 'Test message');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('response_time_ms', $result);
        $this->assertArrayHasKey('channel_response', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsInt($result['response_time_ms']);
        $this->assertGreaterThan(0, $result['response_time_ms']);
    }

    /**
     * Test: Email channel send returns expected structure.
     */
    public function test_email_channel_returns_valid_response(): void
    {
        $channel = new EmailChannel();
        $result = $channel->send('test@example.com', 'Test email');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('response_time_ms', $result);
        $this->assertArrayHasKey('channel_response', $result);
    }

    /**
     * Test: Notification model canRetry logic.
     */
    public function test_notification_can_retry_when_failed_and_under_limit(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'type' => 'sms',
            'recipient' => '+880',
            'message' => 'Test',
            'status' => 'failed',
            'retry_count' => 2,
        ]);

        $this->assertTrue($notification->canRetry());
    }

    public function test_notification_cannot_retry_when_max_retries_exceeded(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'type' => 'sms',
            'recipient' => '+880',
            'message' => 'Test',
            'status' => 'failed',
            'retry_count' => 3,
        ]);

        $this->assertFalse($notification->canRetry());
    }

    public function test_notification_cannot_retry_when_status_is_sent(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'type' => 'sms',
            'recipient' => '+880',
            'message' => 'Test',
            'status' => 'sent',
            'retry_count' => 0,
        ]);

        $this->assertFalse($notification->canRetry());
    }

    /**
     * Test: Circuit breaker starts in closed state.
     */
    public function test_circuit_breaker_starts_closed(): void
    {
        $cb = new CircuitBreakerService('test-channel');
        $cb->reset();

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $cb->getState());
        $this->assertTrue($cb->isAvailable());
    }

    /**
     * Test: Circuit breaker opens after threshold failures.
     */
    public function test_circuit_breaker_opens_after_failures(): void
    {
        $cb = new CircuitBreakerService('test-channel-2', failureThreshold: 3);
        $cb->reset();

        $cb->recordFailure();
        $cb->recordFailure();
        $this->assertTrue($cb->isAvailable()); // Still closed (2 < 3)

        $cb->recordFailure();
        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $cb->getState());
        $this->assertFalse($cb->isAvailable()); // Now open
    }

    /**
     * Test: Circuit breaker resets on success.
     */
    public function test_circuit_breaker_resets_on_success(): void
    {
        $cb = new CircuitBreakerService('test-channel-3', failureThreshold: 2);
        $cb->reset();

        $cb->recordFailure();
        $cb->recordSuccess();

        $this->assertEquals(0, $cb->getFailureCount());
        $this->assertTrue($cb->isAvailable());
    }
}
