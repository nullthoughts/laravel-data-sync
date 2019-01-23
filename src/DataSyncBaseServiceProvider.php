<?php

namespace distinctm\LaravelDataSync;

use Illuminate\Support\ServiceProvider;

class DataSyncBaseServiceProvider extends ServiceProvider
{
    public function boot()
    {

    }

    public function register()
    {
        $this->commands([
            Console\Commands\Sync::class,
        ]);
    }
}
