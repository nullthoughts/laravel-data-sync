<?php

namespace nullthoughts\LaravelDataSync\Console\Commands;

use nullthoughts\LaravelDataSync\Updater;
use Illuminate\Console\Command;

class Sync extends Command
{
    protected $signature = 'data:sync {--path=} {--model=}';

    protected $description = 'Update Models with respective sync data files';

    public function handle()
    {
        $path = $this->option('path');
        $model = $this->option('model');

        $this->info('Updating Models with sync data files');

        (new Updater($path, $model))->run();

        $this->comment('Data sync completed');
    }
}
