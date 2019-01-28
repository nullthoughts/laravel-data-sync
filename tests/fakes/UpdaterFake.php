<?php

namespace distinctm\LaravelDataSync\Tests\Fakes;

use distinctm\LaravelDataSync\Updater;

class UpdaterFake extends Updater
{
    protected function getModel(string $name)
    {
        return '\\distinctm\\LaravelDataSync\\Tests\\' . studly_case(
            pathinfo($name, PATHINFO_FILENAME)
        );
    }
}