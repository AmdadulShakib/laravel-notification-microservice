<?php

namespace App\Services;

use App\Repositories\Interfaces\NotificationLogRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service for AI analytics and dashboard statistics.
 * Provides training data for ML models and dashboard metrics.
 */
class AnalyticsService
{
    public function __construct(
        protected NotificationLogRepositoryInterface $logRepository,
        protected NotificationRepositoryInterface $notificationRepository,
    ) {}

    /**
     * Get structured training data for AI model consumption.
     * Supports filtering and pagination for large dataset export.
     */
    public function getTrainingData(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->logRepository->getTrainingData($filters, $perPage);
    }

    /**
     * Get dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        $statusCounts = $this->notificationRepository->getCountByStatus();
        $channelCounts = $this->notificationRepository->getCountByChannel();
        $avgResponseTime = $this->notificationRepository->getAverageResponseTime();
        $last24h = $this->notificationRepository->getLast24HoursStats();

        $total = array_sum($statusCounts);
        $sent = $statusCounts['sent'] ?? 0;
        $successRate = $total > 0 ? round(($sent / $total) * 100, 2) . '%' : '0%';

        return [
            'total_notifications' => $total,
            'sent' => $sent,
            'failed' => $statusCounts['failed'] ?? 0,
            'pending' => $statusCounts['pending'] ?? 0,
            'processing' => $statusCounts['processing'] ?? 0,
            'success_rate' => $successRate,
            'avg_response_time_ms' => $avgResponseTime ? round($avgResponseTime) : 0,
            'by_channel' => $channelCounts,
            'last_24h' => $last24h,
        ];
    }
}
