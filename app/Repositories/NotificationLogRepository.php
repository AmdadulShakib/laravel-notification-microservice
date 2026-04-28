<?php

namespace App\Repositories;

use App\Models\NotificationLog;
use App\Repositories\Interfaces\NotificationLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationLogRepository implements NotificationLogRepositoryInterface
{
    public function __construct(
        protected NotificationLog $model
    ) {}

    /**
     * Create a new notification log entry (immutable AI training record).
     */
    public function create(array $data): NotificationLog
    {
        return $this->model->create($data);
    }

    /**
     * Get training data with filters and pagination for AI model consumption.
     * Optimized for large dataset export (chunked pagination).
     */
    public function getTrainingData(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        $query->dateRange(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        // Select only fields needed for AI training
        return $query->select([
            'id',
            'notification_id',
            'type',
            'status',
            'retry_count',
            'response_time_ms',
            'sent_at',
            'metadata',
            'failure_reason',
            'channel_response',
            'created_at',
        ])
        ->orderByDesc('created_at')
        ->paginate($perPage);
    }
}
