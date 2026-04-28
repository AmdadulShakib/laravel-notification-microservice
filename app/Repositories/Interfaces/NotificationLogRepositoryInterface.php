<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for NotificationLog (AI training data) operations.
 */
interface NotificationLogRepositoryInterface
{
    public function create(array $data): \App\Models\NotificationLog;

    public function getTrainingData(array $filters, int $perPage = 50): LengthAwarePaginator;
}
