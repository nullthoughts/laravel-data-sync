<?php

namespace nullthoughts\LaravelDataSync\Console\Commands;

use Illuminate\Console\Command;
use nullthoughts\LaravelDataSync\Updater;

class Sync extends Command
{
    protected $signature = 'data:sync {--path=} {--model=}';

    protected $description = 'Update Models with respective sync data files';

    /** @psalm-api */
    public function handle(): void
    {
        $path = $this->option('path');
        $model = $this->option('model');

        $this->info('Updating Models with sync data files');

        (new Updater($path, $model))->run();

        $this->comment('Data sync completed');
    }
}
