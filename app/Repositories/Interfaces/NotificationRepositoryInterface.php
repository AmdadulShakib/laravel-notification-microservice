<?php

namespace App\Repositories\Interfaces;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Notification data access operations.
 * Follows Repository pattern for clean separation of data layer.
 */
interface NotificationRepositoryInterface
{
    public function create(array $data): Notification;

    public function findById(int $id): ?Notification;

    public function findByIdOrFail(int $id): Notification;

    public function updateStatus(int $id, string $status, array $extraData = []): bool;

    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function incrementRetryCount(int $id): bool;

    public function getCountByStatus(): array;

    public function getCountByChannel(): array;

    public function getAverageResponseTime(): ?float;

    public function getLast24HoursStats(): array;
}
