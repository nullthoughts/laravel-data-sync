<?php

namespace nullthoughts\LaravelDataSync;

use Illuminate\Support\ServiceProvider;
use nullthoughts\LaravelDataSync\Console\Commands\Sync;

/** @psalm-api */
class DataSyncBaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    public function register()
    {
        $this->commands([
            Sync::class,
        ]);
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/data-sync.php' => config_path('data-sync.php'),
        ], 'data-sync-config');
    }
}
