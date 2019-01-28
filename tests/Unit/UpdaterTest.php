<?php

namespace distinctm\LaravelDataSync\Tests;

use distinctm\LaravelDataSync\Updater;

class UpdaterTest extends TestCase
{
    /** @test */
    public function exception_is_thrown_if_the_directory_does_not_exists()
    {
        $this->expectException(\Exception::class);

        new Updater();
    }
    
    /** @test */
    public function experiment()
    {
        $updater = new UpdaterFake(__DIR__ . '/../test-data', 'roles');

        \DB::enableQueryLog();
        $updater->run();
        \DB::disableQueryLog();

        dd(\DB::getQueryLog());
    }
}

class UpdaterFake extends Updater
{
    protected function getModel(string $name)
    {
        return Roles::class;
    }
}