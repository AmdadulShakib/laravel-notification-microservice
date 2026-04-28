<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mock JWT Authentication Controller.
 * Generates mock JWT tokens for API testing.
 */
class AuthController extends BaseApiController
{
    /**
     * POST /api/v1/auth/token
     *
     * Generate a mock JWT token for API authentication.
     */
    public function generateToken(Request $request): JsonResponse
    {
        $request->validate([
            'service_name' => ['nullable', 'string'],
        ]);

        $serviceName = $request->get('service_name', 'test-service');

        // Create mock JWT token (header.payload.signature)
        $header = base64_encode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payload = base64_encode(json_encode([
            'iss' => 'notification-microservice',
            'sub' => $serviceName,
            'iat' => time(),
            'exp' => time() + (int) env('JWT_TTL', 60) * 60,
            'service' => $serviceName,
        ]));

        $secret = env('JWT_SECRET', 'default-secret');
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        $token = "{$header}.{$payload}.{$signature}";

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) env('JWT_TTL', 60) * 60,
        ], 'Token generated successfully.');
    }
}
