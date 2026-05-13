<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\SantaCruzFtpClientInterface::class,
            \App\Services\SantaCruz\SantaCruzFtpService::class
        );
    }

    public function boot(): void
    {
        $this->clearSpatiePermissionCacheOnOptimizeClear();
    }

    /**
     * optimize:clear only flushes config/route/view/event caches.
     * Spatie stores permission data in the application cache (key
     * "spatie.permission.cache") which survives optimize:clear, causing
     * stale @can checks (e.g. label buttons invisible after deploy).
     */
    private function clearSpatiePermissionCacheOnOptimizeClear(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if ($event->command === 'optimize:clear' && $event->exitCode === 0) {
                Artisan::call('permission:cache-reset');
            }
        });
    }
}
