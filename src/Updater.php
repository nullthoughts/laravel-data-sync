<?php

namespace distinctm\LaravelDataSync;

use distinctm\LaravelDataSync\Exceptions\ErrorUpdatingModelException;
use distinctm\LaravelDataSync\Exceptions\FileDirectoryNotFoundException;
use distinctm\LaravelDataSync\Exceptions\NoCriteriaException;
use distinctm\LaravelDataSync\Exceptions\NoRecordsInvalidJSONException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use stdClass;

class Updater
{
    /**
     * Get files in sync directory.
     *
     * @param string|null $path
     * @param string|null $model
     *
     * @throws \distinctm\LaravelDataSync\Exceptions\FileDirectoryNotFoundException
     */
    public function __construct($path = null, $model = null)
    {
        $directory = $this->getDirectory($path);
        $this->files = $this->getFiles($directory, $model);
    }

    /**
     * Execute syncModel for each file.
     *
     * @return mixed
     */
    public function run()
    {
        $files = $this->sortModels($this->files);

        return $files->map(function ($file) {
            try {
                return $this->syncModel($file);
            } catch (\ErrorException $e) {
                $model = pathinfo($file, PATHINFO_FILENAME);

                throw new ErrorUpdatingModelException(ucwords($model));
            }
        });
    }

    /**
     * Parse each record for criteria/values and update/create model.
     *
     * @param string $file
     *
     * @throws \distinctm\LaravelDataSync\Exceptions\NoRecordsInvalidJSONException
     *
     * @return \Illuminate\Support\Collection
     */
    protected function syncModel(string $file)
    {
        $model = $this->getModel($file);
        $records = $this->getRecords($file);

        $records->each(function ($record) use ($model) {
            $criteria = $this->resolveObjects(
                $this->getCriteria($record)
            );

            $values = $this->resolveObjects(
                $this->getValues($record)
            );

            $model::updateOrCreate($criteria, $values);
        });

        return $records;
    }

    /**
     * Get directory path for sync files.
     *
     * @param $path
     *
     * @throws \distinctm\LaravelDataSync\Exceptions\FileDirectoryNotFoundException
     *
     * @return string
     */
    protected function getDirectory($path)
    {
        $directory = $path ?? config('data-sync.path', base_path('sync'));

        if (!file_exists($directory)) {
            throw new FileDirectoryNotFoundException();
        }

        return $directory;
    }

    /**
     * Get list of files in directory.
     *
     * @param string      $directory
     * @param string|null $model
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getFiles(string $directory, $model = null)
    {
        if ($model) {
            return Collection::wrap($directory.'/'.$model.'.json');
        }

        return collect(File::files($directory))
            ->filter(function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) == 'json';
            })->map(function ($path) {
                return $path->getPathname();
            });
    }

    /**
     * Sort Models by pre-configured order.
     *
     * @param \Illuminate\Support\Collection $files
     *
     * @return \Illuminate\Support\Collection
     */
    protected function sortModels(\Illuminate\Support\Collection $files)
    {
        if (empty(config('data-sync.order'))) {
            return $files;
        }

        return $files->sortBy(function ($file) use ($files) {
            $filename = pathinfo($file, PATHINFO_FILENAME);

            $order = array_search(
                Str::studly($filename),
                config('data-sync.order')
            );

            return $order !== false ? $order : (count($files) + 1);
        });
    }

    /**
     * Filter record criteria.
     *
     * @param stdClass $record
     *
     * @throws \distinctm\LaravelDataSync\Exceptions\NoCriteriaException
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getCriteria(stdClass $record)
    {
        $criteria = collect($record)->filter(function ($value, $key) {
            return $this->isCriteria($key);
        });

        if ($criteria->count() == 0) {
            throw new NoCriteriaException();
        }

        return $criteria->mapWithKeys(function ($value, $key) {
            return [substr($key, 1) => $value];
        });
    }

    /**
     * Filter record values.
     *
     * @param stdClass $record
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getValues(stdClass $record)
    {
        return collect($record)->reject(function ($value, $key) {
            if ($this->isCriteria($key)) {
                return true;
            }

            if (empty($value)) {
                return true;
            }

            return false;
        });
    }

    /**
     * Returns model name for file.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getModel(string $name)
    {
        return '\\App\\'.Str::studly(pathinfo($name, PATHINFO_FILENAME));
    }

    /**
     * Parses JSON from file and returns collection.
     *
     * @param string $file
     *
     * @throws \distinctm\LaravelDataSync\Exceptions\NoRecordsInvalidJSONException
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getRecords(string $file)
    {
        $records = collect(json_decode(File::get($file)));

        if ($records->isEmpty()) {
            throw new NoRecordsInvalidJSONException($file);
        }

        return $records;
    }

    /**
     * Check if column is criteria for a condition match.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function isCriteria($key)
    {
        return substr($key, 0, 1) == '_';
    }

    /**
     * Return ID for nested key-value pairs.
     *
     * @param string $key
     * @param stdClass $values
     *
     * @return array
     */
    protected function resolveId(string $key, stdClass $values)
    {
        $model = $this->getModel($key);

        $values = collect($values)->mapWithKeys(function ($value, $column) {
            if (is_object($value)) {
                return $this->resolveId($column, $value);
            }

            return [$column => $value];
        })->toArray();

        return [$key.'_id' => $model::where($values)->first()->id];
    }

    /**
     * Detect nested objects and resolve them.
     *
     * @param \Illuminate\Support\Collection $record
     *
     * @return array
     */
    protected function resolveObjects(Collection $record)
    {
        return $record->mapWithKeys(function ($value, $key) {
            if (is_object($value)) {
                return $this->resolveId($key, $value);
            }

            return [$key => $value];
        })->toArray();
    }
}
