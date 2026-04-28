<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        protected Notification $model
    ) {}

    /**
     * Create a new notification record.
     */
    public function create(array $data): Notification
    {
        return $this->model->create($data);
    }

    /**
     * Find notification by ID.
     */
    public function findById(int $id): ?Notification
    {
        return $this->model->find($id);
    }

    /**
     * Find notification by ID or throw exception.
     */
    public function findByIdOrFail(int $id): Notification
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Update notification status with optional extra data.
     */
    public function updateStatus(int $id, string $status, array $extraData = []): bool
    {
        return $this->model->where('id', $id)->update(
            array_merge(['status' => $status], $extraData)
        ) > 0;
    }

    /**
     * Get notifications with filters and pagination.
     * Avoids N+1 by not eager-loading logs unless needed.
     */
    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $query->dateRange(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get notifications by user ID.
     */
    public function getByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Increment the retry count for a notification.
     */
    public function incrementRetryCount(int $id): bool
    {
        return $this->model->where('id', $id)->increment('retry_count') > 0;
    }

    /**
     * Get notification counts grouped by status.
     */
    public function getCountByStatus(): array
    {
        return $this->model
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get notification counts grouped by channel (type).
     */
    public function getCountByChannel(): array
    {
        return $this->model
            ->select('type', 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('type', 'status')
            ->get()
            ->groupBy('type')
            ->map(function ($group) {
                $stats = $group->pluck('count', 'status')->toArray();
                $total = array_sum($stats);
                return [
                    'total' => $total,
                    'sent' => $stats['sent'] ?? 0,
                    'failed' => $stats['failed'] ?? 0,
                    'pending' => $stats['pending'] ?? 0,
                    'processing' => $stats['processing'] ?? 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get the average response time in milliseconds.
     */
    public function getAverageResponseTime(): ?float
    {
        return $this->model
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');
    }

    /**
     * Get notification stats for the last 24 hours.
     */
    public function getLast24HoursStats(): array
    {
        $since = now()->subHours(24);

        $stats = $this->model
            ->where('created_at', '>=', $since)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($stats),
            'sent' => $stats['sent'] ?? 0,
            'failed' => $stats['failed'] ?? 0,
            'pending' => $stats['pending'] ?? 0,
            'processing' => $stats['processing'] ?? 0,
        ];
    }
}
