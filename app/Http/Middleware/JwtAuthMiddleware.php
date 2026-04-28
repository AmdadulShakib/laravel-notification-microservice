<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mock JWT Authentication Middleware.
 *
 * Simulates API Gateway JWT validation.
 * In production, this would validate against a real JWT provider.
 *
 * Accepted format: Authorization: Bearer <token>
 * Mock validation: Token must be a valid base64-decodable string with 3 dot-separated parts (JWT format).
 */
class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please provide a valid Bearer token.',
                'errors' => ['token' => ['Missing Authorization header with Bearer token.']],
            ], 401);
        }

        // Mock JWT validation: check if token has valid JWT structure (header.payload.signature)
        if (!$this->isValidJwtFormat($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
                'errors' => ['token' => ['The provided token is not a valid JWT format.']],
            ], 401);
        }

        // Mock: Extract user info from token payload (decoded)
        $payload = $this->decodePayload($token);
        if ($payload) {
            $request->merge(['jwt_user' => $payload]);
        }

        return $next($request);
    }

    /**
     * Validate JWT format: three base64url-encoded parts separated by dots.
     */
    protected function isValidJwtFormat(string $token): bool
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        // Each part should be a non-empty base64url-encoded string
        foreach ($parts as $part) {
            if (empty($part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Decode the JWT payload (second part).
     */
    protected function decodePayload(string $token): ?array
    {
        $parts = explode('.', $token);

        try {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return is_array($payload) ? $payload : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
