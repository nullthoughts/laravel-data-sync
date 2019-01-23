<?php

namespace distinctm\LaravelDataSync\Console\Commands;

use distinctm\LaravelDataSync\Updater;
use Illuminate\Console\Command;

class Sync extends Command
{
    protected $signature = 'data:sync {--path=}';

    protected $description = 'Update Models with respective sync data files';

    public function handle()
    {
        $path = $this->option('path');

        $this->info('Updating Models with sync data files');
        (new Updater($path))->run();
        $this->comment('Data sync completed');
    }
}
