<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Notification API endpoints.
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Generate a mock JWT token for authentication
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode(['iss' => 'test', 'sub' => 'test-service', 'iat' => time(), 'exp' => time() + 3600]));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", 'secret', true));
        $this->token = "{$header}.{$payload}.{$signature}";
    }

    /**
     * Test: POST /api/v1/notifications/send — success case
     */
    public function test_can_send_notification_successfully(): void
    {
        // Fake queue to prevent job from running synchronously
        \Illuminate\Support\Facades\Queue::fake();

        $response = $this->postJson('/api/v1/notifications/send', [
            'user_id' => 1,
            'type' => 'email',
            'recipient' => 'test@example.com',
            'message' => 'Hello, this is a test notification.',
            'metadata' => [
                'campaign_id' => 'camp-123',
                'campaign_name' => 'Test Campaign',
            ],
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Notification queued successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'type',
                    'recipient',
                    'status',
                    'retry_count',
                    'created_at',
                ],
            ]);

        // Verify notification was created in database with 'pending' status
        $this->assertDatabaseHas('notifications', [
            'user_id' => 1,
            'type' => 'email',
            'recipient' => 'test@example.com',
            'status' => 'pending',
        ]);

        // Verify the job was dispatched to the queue
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendNotificationJob::class);
    }

    /**
     * Test: POST /api/v1/notifications/send — validation failure
     */
    public function test_send_notification_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            // Missing required fields
            'user_id' => '',
            'type' => 'invalid_type',
        ], [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonStructure([
                'errors' => [
                    'user_id',
                    'type',
                    'recipient',
                    'message',
                ],
            ]);
    }

    /**
     * Test: GET /api/v1/notifications/{id}/status — success
     */
    public function test_can_get_notification_status(): void
    {
        $notification = Notification::create([
            'user_id' => 1,
            'type' => 'sms',
            'recipient' => '+8801234567890',
            'message' => 'Test SMS',
            'status' => 'sent',
            'response_time_ms' => 234,
            'sent_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/notifications/{$notification->id}/status", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $notification->id,
                    'status' => 'sent',
                    'type' => 'sms',
                ],
            ]);
    }

    /**
     * Test: GET /api/v1/notifications/{id}/status — not found
     */
    public function test_get_notification_status_returns_404(): void
    {
        $response = $this->getJson('/api/v1/notifications/99999/status', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Notification not found.',
            ]);
    }

    /**
     * Test: GET /api/v1/notifications — list with filters
     */
    public function test_can_list_notifications_with_filters(): void
    {
        // Create test notifications
        Notification::create(['user_id' => 1, 'type' => 'email', 'recipient' => 'a@test.com', 'message' => 'Test 1', 'status' => 'sent']);
        Notification::create(['user_id' => 1, 'type' => 'sms', 'recipient' => '+880123', 'message' => 'Test 2', 'status' => 'failed']);
        Notification::create(['user_id' => 2, 'type' => 'email', 'recipient' => 'b@test.com', 'message' => 'Test 3', 'status' => 'sent']);

        $response = $this->getJson('/api/v1/notifications?status=sent&type=email', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta' => ['total' => 2],
            ]);
    }

    /**
     * Test: JWT authentication — unauthorized access without token
     */
    public function test_unauthorized_access_without_token(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            'user_id' => 1,
            'type' => 'email',
            'recipient' => 'test@example.com',
            'message' => 'Test',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Authentication required. Please provide a valid Bearer token.',
            ]);
    }

    /**
     * Test: JWT authentication — invalid token format
     */
    public function test_unauthorized_access_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            'user_id' => 1,
            'type' => 'email',
            'recipient' => 'test@example.com',
            'message' => 'Test',
        ], [
            'Authorization' => 'Bearer invalid-token-without-dots',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired token.',
            ]);
    }

    /**
     * Test: GET /api/v1/analytics/training-data
     */
    public function test_can_get_training_data(): void
    {
        $response = $this->getJson('/api/v1/analytics/training-data', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => ['total', 'page', 'per_page', 'last_page'],
            ]);
    }

    /**
     * Test: GET /api/v1/dashboard/stats
     */
    public function test_can_get_dashboard_stats(): void
    {
        // Create some notifications for stats
        Notification::create(['user_id' => 1, 'type' => 'email', 'recipient' => 'a@test.com', 'message' => 'Test', 'status' => 'sent', 'response_time_ms' => 200]);
        Notification::create(['user_id' => 1, 'type' => 'sms', 'recipient' => '+880', 'message' => 'Test', 'status' => 'failed']);

        $response = $this->getJson('/api/v1/dashboard/stats', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_notifications',
                    'sent',
                    'failed',
                    'pending',
                    'success_rate',
                    'avg_response_time_ms',
                    'by_channel',
                    'last_24h',
                ],
            ]);
    }

    /**
     * Test: POST /api/v1/auth/token — token generation
     */
    public function test_can_generate_auth_token(): void
    {
        $response = $this->postJson('/api/v1/auth/token', [
            'service_name' => 'test-service',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Token generated successfully.',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_in'],
            ]);
    }

    /**
     * Test: GET /api/v1/health — health check
     */
    public function test_health_check(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'version' => 'v1',
            ]);
    }
}
