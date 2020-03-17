<?php

namespace nullthoughts\LaravelDataSync\Tests\Fakes;

use nullthoughts\LaravelDataSync\Updater;
use Illuminate\Support\Str;

class UpdaterFake extends Updater
{
    protected function getModel(string $name)
    {
        return '\\nullthoughts\\LaravelDataSync\\Tests\\'.Str::studly(
                pathinfo($name, PATHINFO_FILENAME)
            );
    }
}