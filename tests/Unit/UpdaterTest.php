<?php

namespace nullthoughts\LaravelDataSync\Tests;

use nullthoughts\LaravelDataSync\Exceptions\ErrorUpdatingModelException;
use nullthoughts\LaravelDataSync\Tests\fakes\UpdaterFake;
use Exception;

class UpdaterTest extends TestCase
{
    /** @test */
    public function it_adds_roles_to_the_database()
    {
        $updater = new UpdaterFake(__DIR__.'/../test-data', 'roles');

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_default_to_configuration()
    {
        config()->set('data-sync.path', __DIR__.'/../test-data');

        $updater = new UpdaterFake();

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_update_an_existing_record()
    {
        config()->set('data-sync.path', __DIR__.'/../test-data');
        (new UpdaterFake())->run();

        config()->set('data-sync.path', __DIR__.'/../test-data/valid');
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

        config()->set('data-sync.path', __DIR__.'/../test-data/relationship', 'roles');
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
            $updater = new UpdaterFake(__DIR__.'/../test-data/invalid-json');
            $updater->run();

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertStringContainsString('No records or invalid JSON for', $e->getMessage());
        }
    }

    /** @test */
    public function the_json_must_contain_a_key_with_an_underscore()
    {
        try {
            $updater = new UpdaterFake(__DIR__.'/../test-data/no-criteria');
            $updater->run();

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertEquals('No criteria/attributes detected', $e->getMessage());
        }
    }

    /** @test */
    public function order_of_imports_can_be_defined_in_config()
    {
        config()->set('data-sync.order', [
            'Supervisor',
            'Roles',
        ]);

        $updater = new UpdaterFake(__DIR__.'/../test-data/ordered');
        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('supervisors', ['name' => 'CEO']);
    }

    /** @test */
    public function exception_is_thrown_if_imports_are_in_incorrect_order()
    {
        config()->set('data-sync.order', [
            'Roles',
            'Supervisor',
        ]);

        $this->expectException(ErrorUpdatingModelException::class);

        $updater = new UpdaterFake(__DIR__.'/../test-data/ordered');
        $updater->run();
    }

    /** @test */
    public function it_ignores_non_json_files()
    {
        $updater = new UpdaterFake(__DIR__.'/../test-data/not-json');
        $updater->run();

        $this->assertDatabaseMissing('roles', ['slug' => 'update-student-records']);
    }
}
