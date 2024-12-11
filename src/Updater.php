<?php

namespace nullthoughts\LaravelDataSync;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use nullthoughts\LaravelDataSync\Exceptions\ErrorUpdatingModelException;
use nullthoughts\LaravelDataSync\Exceptions\FileDirectoryNotFoundException;
use nullthoughts\LaravelDataSync\Exceptions\NoCriteriaException;
use nullthoughts\LaravelDataSync\Exceptions\NoRecordsInvalidJSONException;
use stdClass;

class Updater
{
    private string $directory;

    private Collection $files;

    private bool $remote;

    private string $disk;

    private string $baseNamespace = '\\App\\';

    /**
     * Get files in sync directory.
     *
     * @param  string|null  $path
     * @param  string|null  $model
     * @param  bool  $remote
     * @param  string  $disk
     *
     * @throws \nullthoughts\LaravelDataSync\Exceptions\FileDirectoryNotFoundException
     */
    public function __construct($path = null, $model = null, $remote = false, $disk = 's3')
    {
        $this->remote = $remote;
        $this->disk = $disk;

        $this->directory = $this->getDirectory($path);
        $this->files = $this->getFiles($this->directory, $model);
    }

    /**
     * Override the default namespace for the class.
     *
     * @psalm-api
     */
    public function setNamespace(string $namespace): void
    {
        $this->baseNamespace = $namespace;
    }

    /**
     * Override the default directory for the class.
     *
     * @psalm-api
     */
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * Execute syncModel for each file.
     */
    public function run()
    {
        $files = $this->sortModels($this->files);

        return $files->map(function ($file) {
            try {
                return $this->syncModel($file);
            } catch (\ErrorException) {
                $model = pathinfo($file, PATHINFO_FILENAME);

                throw new ErrorUpdatingModelException(ucwords($model));
            }
        });
    }

    /**
     * Parse each record for criteria/values and update/create model.
     *
     *
     * @return \Illuminate\Support\Collection
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \nullthoughts\LaravelDataSync\Exceptions\NoRecordsInvalidJSONException
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
     *
     * @return string
     *
     * @throws \nullthoughts\LaravelDataSync\Exceptions\FileDirectoryNotFoundException
     */
    protected function getDirectory(?string $path)
    {
        $directory = $path ?? config('data-sync.path', base_path('sync'));

        if ($this->directoryMissingLocally($directory) || $this->directoryMissingRemotely($directory)) {
            throw new FileDirectoryNotFoundException;
        }

        $this->directory = $directory;

        return $this->directory;
    }

    /**
     * Get list of files in directory.
     *
     * @param  string|null  $model
     * @return \Illuminate\Support\Collection
     */
    protected function getFiles(string $directory, $model = null)
    {
        if ($model) {
            return Collection::wrap($directory.'/'.$model.'.json');
        }

        $files = ($this->remote) ? Storage::disk($this->disk)->files($directory) : File::files($directory);

        return collect($files)
            ->filter(function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) == 'json';
            })->map(function ($path) {

                if (is_string($path)) {
                    return $path;
                }

                return $path->getPathname();
            });
    }

    /**
     * Sort Models by pre-configured order.
     *
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
     *
     * @return \Illuminate\Support\Collection
     *
     * @throws \nullthoughts\LaravelDataSync\Exceptions\NoCriteriaException
     */
    protected function getCriteria(stdClass $record)
    {
        $criteria = collect($record)->filter(function ($value, $key) {
            return $this->isCriteria($key);
        });

        if ($criteria->count() == 0) {
            throw new NoCriteriaException;
        }

        return $criteria->mapWithKeys(function ($value, $key) {
            return [substr($key, 1) => $value];
        });
    }

    /**
     * Filter record values.
     *
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
     *
     * @return string
     */
    protected function getModel(string $name)
    {
        return $this->baseNamespace.Str::studly(pathinfo($name, PATHINFO_FILENAME));
    }

    /**
     * Parses JSON from file and returns collection.
     *
     *
     * @return \Illuminate\Support\Collection
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \nullthoughts\LaravelDataSync\Exceptions\NoRecordsInvalidJSONException
     */
    protected function getRecords(string $file)
    {
        $fetchedFile = ($this->remote) ? Storage::disk($this->disk)->get($file) : File::get($file);

        $records = collect(json_decode($fetchedFile));

        if ($records->isEmpty()) {
            throw new NoRecordsInvalidJSONException($file);
        }

        return $records;
    }

    /**
     * Check if column is criteria for a condition match.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isCriteria($key)
    {
        return substr($key, 0, 1) == '_';
    }

    /**
     * Return ID for nested key-value pairs.
     *
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

    /**
     * @param  \Illuminate\Config\Repository  $directory
     * @return bool
     */
    protected function directoryMissingLocally($directory)
    {
        return ! $this->remote && ! file_exists($directory);
    }

    /**
     * @param  \Illuminate\Config\Repository  $directory
     * @return bool
     */
    protected function directoryMissingRemotely($directory)
    {
        return $this->remote && ! Storage::disk($this->disk)->exists($directory);
    }
}
