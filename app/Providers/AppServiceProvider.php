<?php

namespace App\Providers;

use App\Events\NotificationFailed;
use App\Events\NotificationSent;
use App\Listeners\LogNotificationFailed;
use App\Listeners\LogNotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners for notification lifecycle
        Event::listen(NotificationSent::class, LogNotificationSent::class);
        Event::listen(NotificationFailed::class, LogNotificationFailed::class);
    }
}
