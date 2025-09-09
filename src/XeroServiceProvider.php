<?php

namespace Almani\Xero;

use Illuminate\Support\ServiceProvider;
use Almani\Xero\Services\XeroService;

class XeroServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Lazy-load the Xero service to prevent config missing errors
        $this->app->singleton('xero', function ($app) {
            $config = config('xero', []); // fallback to empty array
            return new XeroService($config);
        });
    }
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Publish the config safely
            $this->publishes([
                __DIR__ . '/Config/xero.php' => config_path('xero.php')
            ], 'config');

            // Example: register commands if needed
            if (class_exists(Console\SyncXeroInvoices::class)) {
                $this->commands([Console\SyncXeroInvoices::class]);
            }
        }
    }
}
