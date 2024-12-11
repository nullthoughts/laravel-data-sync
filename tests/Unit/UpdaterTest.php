<?php

namespace nullthoughts\LaravelDataSync\Tests\Unit;

use Exception;
use nullthoughts\LaravelDataSync\Exceptions\ErrorUpdatingModelException;
use nullthoughts\LaravelDataSync\Tests\fakes\UpdaterFake;
use nullthoughts\LaravelDataSync\Tests\Roles;
use nullthoughts\LaravelDataSync\Tests\Supervisor;
use nullthoughts\LaravelDataSync\Tests\TestCase;

class UpdaterTest extends TestCase
{
    /** @test */
    public function it_adds_roles_to_the_database(): void
    {
        $updater = new UpdaterFake($this->testDataPath, 'roles');

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_default_to_configuration()
    {
        config()->set('data-sync.path', $this->testDataPath);

        $updater = new UpdaterFake;

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_update_an_existing_record()
    {
        config()->set('data-sync.path', $this->testDataPath);
        (new UpdaterFake)->run();

        config()->set('data-sync.path', $this->testDataPath.'/valid');
        (new UpdaterFake)->run();

        $this->assertDatabaseHas('roles', ['category' => 'changed']);
        $this->assertDatabaseHas('roles', ['category' => 'changed']);
        $this->assertDatabaseHas('roles', ['category' => 'changed']);
    }

    /** @test */
    public function it_can_update_the_relationship(): void
    {
        $supervisor = Supervisor::create([
            'name' => 'CEO',
        ]);

        config()->set('data-sync.path', $this->testDataPath.'/relationship', 'roles');
        (new UpdaterFake)->run();

        $this->assertEquals($supervisor->id, Roles::first()->supervisor_id);
        $this->assertTrue($supervisor->is(Roles::first()->supervisor));
    }

    /** @test */
    public function exception_is_thrown_if_the_directory_does_not_exists(): void
    {
        try {
            new UpdaterFake;

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertEquals('Specified sync file directory does not exist', $e->getMessage());
        }
    }

    /** @test */
    public function invalid_json_throws_an_exception(): void
    {
        try {
            $updater = new UpdaterFake($this->testDataPath.'/invalid-json');
            $updater->run();

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertStringContainsString('No records or invalid JSON for', $e->getMessage());
        }
    }

    /** @test */
    public function the_json_must_contain_a_key_with_an_underscore(): void
    {
        try {
            $updater = new UpdaterFake($this->testDataPath.'/no-criteria');
            $updater->run();

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertEquals('No criteria/attributes detected', $e->getMessage());
        }
    }

    /** @test */
    public function order_of_imports_can_be_defined_in_config(): void
    {
        config()->set('data-sync.order', [
            'Supervisor',
            'Roles',
        ]);

        $updater = new UpdaterFake($this->testDataPath.'/ordered');
        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('supervisors', ['name' => 'CEO']);
    }

    public function exception_is_thrown_if_imports_are_in_incorrect_order(): void
    {
        config()->set('data-sync.order', [
            'Roles',
            'Supervisor',
        ]);

        $this->expectException(ErrorUpdatingModelException::class);

        $updater = new UpdaterFake($this->testDataPath.'/ordered');
        $updater->run();
    }

    /** @test */
    public function it_ignores_non_json_files(): void
    {
        $updater = new UpdaterFake($this->testDataPath.'/not-json');
        $updater->run();

        $this->assertDatabaseMissing('roles', ['slug' => 'update-student-records']);
    }
}
