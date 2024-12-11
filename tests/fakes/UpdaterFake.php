<?php

namespace nullthoughts\LaravelDataSync\Tests\Fakes;

use Illuminate\Support\Str;
use nullthoughts\LaravelDataSync\Updater;

class UpdaterFake extends Updater
{
    protected function getModel(string $name)
    {
        return '\\nullthoughts\\LaravelDataSync\\Tests\\'.Str::studly(
            pathinfo($name, PATHINFO_FILENAME)
        );
    }
}
