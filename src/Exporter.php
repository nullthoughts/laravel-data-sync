<?php

namespace nullthoughts\LaravelDataSync;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use nullthoughts\LaravelDataSync\Exceptions\FileDirectoryNotFoundException;

class Exporter
{
    private $modelName;

    private $criteria;

    private $except;

    private $only;

    /**
     * Get files in sync directory.
     *
     * @param  string  $model
     * @param  array  $criteria
     *
     * @throws \nullthoughts\LaravelDataSync\Exceptions\FileDirectoryNotFoundException
     */
    public function __construct(string $modelName, array $criteria = [], $except = [], $only = [])
    {
        $this->modelName = $modelName;
        $this->criteria = $criteria;
        $this->except = $except;
        $this->only = $only;
    }

    /**
     * Export Model data to sync file.
     *
     * @return mixed
     */
    public function run()
    {
        $class = config('data-sync.namespace', '\\App\\Models\\') . $this->modelName;
        $filename = $this->getDirectory() . '/' . $this->modelName . '.json';
        $data = $this->prepareData($class);

        file_put_contents($filename, $data);
    }

    /**
     * Get directory path for sync files.
     *
     * @throws \nullthoughts\LaravelDataSync\Exceptions\FileDirectoryNotFoundException
     *
     * @return string
     */
    protected function getDirectory()
    {
        $directory = config('data-sync.path', base_path('sync'));

        if (! file_exists($directory)) {
            throw new FileDirectoryNotFoundException();
        }

        return $directory;
    }

    protected function prepareData($class)
    {
        $class = new $class;
        $keys = $this->prepareKeys($class);

        return $class->select($keys)->get()->toJson();
    }

    protected function prepareKeys($class)
    {
        $keys = Schema::getColumnListing($class->getTable());

        if ($this->only) {
            $keys = array_intersect($keys, $this->only);
        }

        if ($this->except) {
            $keys = array_diff($keys, $this->except);
        }
        

        return collect($keys)->map(function ($key) {
            if (in_array($key, $this->criteria)) {
                return $key . ' as _' . $key;
            }

            return $key;
        })->toArray();
    }
}
