<?php

namespace distinctm\LaravelDataSync\Tests;

use distinctm\LaravelDataSync\Tests\Fakes\UpdaterFake;
use Exception;

class UpdaterTest extends TestCase
{
    /** @test */
    public function it_adds_roles_to_the_database()
    {
        $updater = new UpdaterFake(__DIR__ . '/../test-data', 'roles');

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_default_to_configuration()
    {
        config()->set('data-sync.path', __DIR__ . '/../test-data');

        $updater = new UpdaterFake();

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_update_an_existing_record()
    {
        config()->set('data-sync.path', __DIR__ . '/../test-data');
        (new UpdaterFake())->run();

        config()->set('data-sync.path', __DIR__ . '/../test-data/valid');
        (new UpdaterFake())->run();

        $this->assertDatabaseHas('roles', ['category' => 'changed']);
        $this->assertDatabaseHas('roles', ['category' => 'changed']);
        $this->assertDatabaseHas('roles', ['category' => 'changed']);
    }

    /** @test */
    public function it_can_update_the_relationship()
    {
        $supervisor = Supervisor::create([
            'name' => 'CEO',
        ]);

        config()->set('data-sync.path', __DIR__ . '/../test-data/relationship', 'roles');
        (new UpdaterFake())->run();

        $this->assertEquals($supervisor->id, Roles::first()->supervisor_id);
        $this->assertTrue($supervisor->is(Roles::first()->supervisor));
    }

    /** @test */
    public function exception_is_thrown_if_the_directory_does_not_exists()
    {
        try {
            new UpdaterFake();

            $this->fail('exception was thrown');

        } catch (Exception $e) {
            $this->assertEquals('Specified sync file directory does not exist', $e->getMessage());
        }
    }
    
    /** @test */
    public function invalid_json_throws_an_exception()
    {
        try {
            $updater = new UpdaterFake(__DIR__ . '/../test-data/invalid-json');
            $updater->run();

            $this->fail('exception was thrown');

        } catch (Exception $e) {
            $this->assertContains('No records or invalid JSON for', $e->getMessage());
        }

    }

    /** @test */
    public function the_json_must_contain_a_key_with_an_underscore()
    {
        try {
            $updater = new UpdaterFake(__DIR__ . '/../test-data/no-criteria');
            $updater->run();

            $this->fail('exception was thrown');

        } catch (Exception $e) {
           $this->assertEquals('No criteria/attributes detected', $e->getMessage());
        }

    }
}