<?php

namespace App\Providers;

use App\Repositories\Interfaces\NotificationLogRepositoryInterface;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Repositories\NotificationLogRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository interface to implementation bindings.
     */
    public array $bindings = [
        NotificationRepositoryInterface::class => NotificationRepository::class,
        NotificationLogRepositoryInterface::class => NotificationLogRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
