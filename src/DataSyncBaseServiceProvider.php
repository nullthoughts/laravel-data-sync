<?php

namespace nullthoughts\LaravelDataSync;

use Illuminate\Support\ServiceProvider;

class DataSyncBaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    public function register()
    {
        $this->commands([
            Console\Commands\Sync::class,
            Console\Commands\Export::class,
        ]);
    }

    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__.'/../config/data-sync.php' => config_path('data-sync.php'),
        ], 'data-sync-config');
    }
}
