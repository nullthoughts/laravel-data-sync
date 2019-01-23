<?php

namespace distinctm\LaravelDataSync\Console\Commands;

use distinctm\LaravelDataSync\Updater;
use Illuminate\Console\Command;

class Sync extends Command
{
    protected $signature = 'data:sync';

    protected $description = 'Update Models with respective sync data files';

    public function handle()
    {
        $this->info('Updating Models with sync data files');
        (new Updater)->run();
        $this->comment('Data sync completed');
    }
}
