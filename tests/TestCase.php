<?php

namespace nullthoughts\LaravelDataSync\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testdb');
        $app['config']->set('database.connections.testdb', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('supervisors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->unsignedInteger('supervisor_id')->nullable();
            $table->string('category')->nullable();
        });
    }
}
