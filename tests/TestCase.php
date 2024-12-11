<?php

namespace nullthoughts\LaravelDataSync\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    public string $testDataPath;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->testDataPath = __DIR__.'/test-data';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('supervisors', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('roles', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('slug');
            $table->unsignedInteger('supervisor_id')->nullable();
            $table->string('category')->nullable();
        });
    }
}
