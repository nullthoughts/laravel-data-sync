<?php

namespace nullthoughts\LaravelDataSync\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use nullthoughts\LaravelDataSync\Exceptions\ErrorUpdatingModelException;
use nullthoughts\LaravelDataSync\Tests\fakes\UpdaterFake;
use Exception;

class UpdaterRemoteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        Storage::disk('s3')->put('test-data/roles.json', File::get(__DIR__.'/../test-data/roles.json'));
        foreach (File::directories(__DIR__.'/../test-data/') as $directory) {
            $files = File::files($directory);

            foreach ($files as $file) {
                Storage::disk('s3')->put('test-data/'.basename($directory).'/'.$file->getRelativePathname(), File::get($file->getPathname()));
            }
        }
    }

    /** @test */
    public function it_adds_roles_to_the_database_in_remote()
    {
        $updater = new UpdaterFake('test-data', 'roles', true, 's3');

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_default_to_configuration_in_remote()
    {
        config()->set('data-sync.path', 'test-data');

        $updater = new UpdaterFake(null, null, true, 's3');

        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('roles', ['slug' => 'borrow-ferrari']);
        $this->assertDatabaseHas('roles', ['slug' => 'destroy-ferrari']);
    }

    /** @test */
    public function it_can_update_an_existing_record_in_remote()
    {
        config()->set('data-sync.path', 'test-data');
        (new UpdaterFake(null, null, true, 's3'))->run();

        config()->set('data-sync.path', 'test-data/valid');
        (new UpdaterFake(null, null, true, 's3'))->run();

        $this->assertDatabaseHas('roles', ['category' => 'changed']);
        $this->assertDatabaseHas('roles', ['category' => 'changed']);
        $this->assertDatabaseHas('roles', ['category' => 'changed']);
    }

    /** @test */
    public function it_can_update_the_relationship_in_remote()
    {
        $supervisor = Supervisor::create([
            'name' => 'CEO',
        ]);

        config()->set('data-sync.path', 'test-data/relationship');
        (new UpdaterFake(null, null, true, 's3'))->run();

        $this->assertEquals($supervisor->id, Roles::first()->supervisor_id);
        $this->assertTrue($supervisor->is(Roles::first()->supervisor));
    }

    /**
     * @test
     * @group current
     */
    public function exception_is_thrown_if_the_directory_does_not_exists()
    {
        try {
            new UpdaterFake(null, null, true, 's3');

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertEquals('Specified sync file directory does not exist', $e->getMessage());
        }
    }

    /** @test */
    public function invalid_json_throws_an_exception_in_remote()
    {
        try {
            $updater = new UpdaterFake('test-data/invalid-json', null, true, 's3');
            $updater->run();

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertStringContainsString('No records or invalid JSON for', $e->getMessage());
        }
    }

    /** @test */
    public function the_json_must_contain_a_key_with_an_underscore_in_remote()
    {
        try {
            $updater = new UpdaterFake('test-data/no-criteria', null, true, 's3');
            $updater->run();

            $this->fail('exception was thrown');
        } catch (Exception $e) {
            $this->assertEquals('No criteria/attributes detected', $e->getMessage());
        }
    }

    /** @test */
    public function order_of_imports_can_be_defined_in_config_in_remote()
    {
        config()->set('data-sync.order', [
            'Supervisor',
            'Roles',
        ]);

        $updater = new UpdaterFake('test-data/ordered', null, true, 's3');
        $updater->run();

        $this->assertDatabaseHas('roles', ['slug' => 'update-student-records']);
        $this->assertDatabaseHas('supervisors', ['name' => 'CEO']);
    }

    /** @test */
    public function exception_is_thrown_if_imports_are_in_incorrect_order_in_remote()
    {
        config()->set('data-sync.order', [
            'Roles',
            'Supervisor',
        ]);

        $this->expectException(ErrorUpdatingModelException::class);

        $updater = new UpdaterFake('test-data/ordered', null, true, 's3');
        $updater->run();
    }

    /** @test */
    public function it_ignores_non_json_files_in_remote()
    {
        $updater = new UpdaterFake('test-data/not-json', null, true, 's3');
        $updater->run();

        $this->assertDatabaseMissing('roles', ['slug' => 'update-student-records']);
    }
}
