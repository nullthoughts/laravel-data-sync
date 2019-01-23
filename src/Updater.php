<?php

namespace distinctm\LaravelDataSync;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class Updater
{

    /**
     * Get files in sync directory
     */
    public function __construct()
    {
        $this->files = Storage::disk('sync')->files();
    }

    /**
     * Execute syncModel for each file
     *
     * @return void
     */
    public function run()
    {
        collect($this->files)->each(function($file) {
            $this->syncModel($file);
        });
    }

    /**
     * Parse each record for attributes/values and update/create model
     *
     * @param string $file
     * @return \Illuminate\Support\Collection
     */
    protected function syncModel(string $file)
    {
        $model = $this->getModel($file);
        $records = $this->getRecords($file);

        return $records->map(function($record) use ($model) {
            $attributes = $this->getAttributes($record);
            $values = $this->getValues($record);

            return $model::updateOrCreate($attributes, $values);
        });
    }

    /**
     * Filter record attributes
     *
     * @param object $record
     * @return array
     */
    protected function getAttributes(object $record)
    {
        $attributes = collect($record)->filter(function($value, $key) {
            return $this->isAttribute($key);
        });

        if($attributes->count() == 0) {
            throw new \Exception("No attributes (conditions for a match) detected");
        }

        return $attributes->mapWithKeys(function($value, $key) {
            return [substr($key, 1) => $value];
        })->toArray();
    }

    /**
     * Filter record values
     *
     * @param array $record
     * @return array
     */
    protected function getValues($record)
    {
        $values = collect($record)->reject(function($value, $key) {
            if($this->isAttribute($key)) {
                return true;
            }

            if(empty($value)) {
                return true;
            }

            return false;
        });

        return $values->mapWithKeys(function($value, $key) {
            if(is_object($value)) {
                return $this->resolveId($key, $value);
            }

            return [$key => $value];
        })->toArray();
    }

    /**
     * Returns model name for file
     *
     * @param string $file
     * @return string
     */
    protected function getModel(string $name)
    {
        return '\\App\\' . pathinfo($name, PATHINFO_FILENAME);
    }

    /**
     * Parses file to JSON and returns collection
     *
     * @param string $file
     * @return \Illuminate\Support\Collection
     */
    protected function getRecords(string $file)
    {
        return collect(json_decode(Storage::disk('sync')->get($file)));
    }

    /**
     * Check if column is an attribute
     *
     * @param string $key
     * @return boolean
     */
    protected function isAttribute($key)
    {
        return substr($key, 0, 1) == '_';
    }

    /**
     * Return ID for nested key-value pairs
     *
     * @param string $key
     * @param object $values
     * @return array
     */
    protected function resolveId(string $key, object $values)
    {
        $model = $this->getModel($key);

        $values = collect($values)->mapWithKeys(function($value, $column) {
            if(is_object($value)) {
                return $this->resolveId($column, $value);
            }

            return [$column => $value];
        })->toArray();

        return [$key . '_id' => $model::where($values)->first()->id];
    }

}
