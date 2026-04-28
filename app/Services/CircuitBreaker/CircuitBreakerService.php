<?php

namespace App\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;

/**
 * Circuit Breaker Pattern Implementation.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Too many failures, requests are blocked
 * - HALF_OPEN: Testing if service has recovered
 *
 * Prevents cascading failures when a notification channel is down.
 */
class CircuitBreakerService
{
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';

    protected int $failureThreshold;
    protected int $resetTimeoutSeconds;
    protected int $halfOpenMaxAttempts;

    public function __construct(
        protected string $serviceName,
        int $failureThreshold = 5,
        int $resetTimeoutSeconds = 30,
        int $halfOpenMaxAttempts = 1
    ) {
        $this->failureThreshold = $failureThreshold;
        $this->resetTimeoutSeconds = $resetTimeoutSeconds;
        $this->halfOpenMaxAttempts = $halfOpenMaxAttempts;
    }

    /**
     * Check if the circuit allows requests to pass through.
     */
    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN) {
            // Check if reset timeout has elapsed
            $lastFailedAt = Cache::get($this->cacheKey('last_failed_at'), 0);
            if ((time() - $lastFailedAt) >= $this->resetTimeoutSeconds) {
                $this->transitionTo(self::STATE_HALF_OPEN);
                return true;
            }
            return false;
        }

        // HALF_OPEN: Allow limited requests to test recovery
        $halfOpenAttempts = (int) Cache::get($this->cacheKey('half_open_attempts'), 0);
        return $halfOpenAttempts < $this->halfOpenMaxAttempts;
    }

    /**
     * Record a successful operation.
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Service recovered, close the circuit
            $this->reset();
        }

        // Reset consecutive failure count on success
        Cache::put($this->cacheKey('failure_count'), 0, 3600);
    }

    /**
     * Record a failed operation.
     */
    public function recordFailure(): void
    {
        $failures = (int) Cache::get($this->cacheKey('failure_count'), 0);
        $failures++;

        Cache::put($this->cacheKey('failure_count'), $failures, 3600);
        Cache::put($this->cacheKey('last_failed_at'), time(), 3600);

        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Failed during half-open test, re-open circuit
            $this->transitionTo(self::STATE_OPEN);
            return;
        }

        if ($failures >= $this->failureThreshold) {
            $this->transitionTo(self::STATE_OPEN);
        }
    }

    /**
     * Get the current circuit state.
     */
    public function getState(): string
    {
        return Cache::get($this->cacheKey('state'), self::STATE_CLOSED);
    }

    /**
     * Get current failure count.
     */
    public function getFailureCount(): int
    {
        return (int) Cache::get($this->cacheKey('failure_count'), 0);
    }

    /**
     * Reset the circuit breaker to closed state.
     */
    public function reset(): void
    {
        Cache::put($this->cacheKey('state'), self::STATE_CLOSED, 3600);
        Cache::put($this->cacheKey('failure_count'), 0, 3600);
        Cache::put($this->cacheKey('half_open_attempts'), 0, 3600);
    }

    /**
     * Transition to a new state.
     */
    protected function transitionTo(string $state): void
    {
        Cache::put($this->cacheKey('state'), $state, 3600);

        if ($state === self::STATE_HALF_OPEN) {
            Cache::put($this->cacheKey('half_open_attempts'), 0, 3600);
        }
    }

    /**
     * Generate a cache key scoped to this service.
     */
    protected function cacheKey(string $suffix): string
    {
        return "circuit_breaker:{$this->serviceName}:{$suffix}";
    }
}
