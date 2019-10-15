<?php

namespace distinctm\LaravelDataSync\Tests\Fakes;

use distinctm\LaravelDataSync\Updater;
use Illuminate\Support\Str;

class UpdaterFake extends Updater
{
    protected function getModel(string $name)
    {
        return '\\distinctm\\LaravelDataSync\\Tests\\'.Str::studly(
                pathinfo($name, PATHINFO_FILENAME)
            );
    }
}