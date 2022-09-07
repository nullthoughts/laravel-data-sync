<?php

namespace nullthoughts\LaravelDataSync\Console\Commands;

use nullthoughts\LaravelDataSync\Exporter;
use Illuminate\Console\Command;

class Export extends Command
{
    protected $signature = 'data:export {model} {--criteria=*} {--except=*} {--only=*}';

    protected $description = 'Export Model data for synchronization';

    public function handle()
    {
        $model = $this->argument('model');

        $this->info('Exporting ' . $model . ' model to sync data file');

        (new Exporter($model, $this->option('criteria'), $this->option('except'), $this->option('only')))->run();

        $this->comment('Export for data sync completed');
    }
}
