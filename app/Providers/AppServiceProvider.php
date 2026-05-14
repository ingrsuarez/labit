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
        $this->app->bind(\App\Contracts\SantaCruzFtpClientInterface::class, function ($app) {
            $ssl = (bool) $app['config']->get('santacruz.ftp.ssl', false);
            // FTPS explícito (AUTH TLS) con IIS: cURL suele ser más fiable que ftp_ssl_connect (muchas veces implícito).
            if ($ssl && extension_loaded('curl')) {
                return new \App\Services\SantaCruz\SantaCruzFtpCurlService;
            }
            if (\function_exists('ftp_connect')) {
                return new \App\Services\SantaCruz\SantaCruzFtpService;
            }
            if (extension_loaded('curl')) {
                return new \App\Services\SantaCruz\SantaCruzFtpCurlService;
            }

            return new \App\Services\SantaCruz\SantaCruzFtpService;
        });
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
