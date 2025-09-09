<?php
namespace Almani\Xero;
use Illuminate\Support\ServiceProvider;
class XeroServiceProvider extends ServiceProvider
{
    public function register(){
        $this->app->singleton('xero', function($app){
            return new Services\XeroService(config('xero'));
        });
    }
    public function boot(){
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/Config/xero.php'=>config_path('xero.php')],'config');
            $this->commands([Console\SyncXeroInvoices::class]);
        }
    }
}