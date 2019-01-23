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
     * Parse each record for criteria/values and update/create model
     *
     * @param string $file
     * @return \Illuminate\Support\Collection
     */
    protected function syncModel(string $file)
    {
        $model = $this->getModel($file);
        $records = $this->getRecords($file);

        return $records->map(function($record) use ($model) {
            $criteria = $this->getCriteria($record);
            $values = $this->getValues($record);

            return $model::updateOrCreate($criteria, $values);
        });
    }

    /**
     * Filter record criteria
     *
     * @param object $record
     * @return array
     */
    protected function getCriteria(object $record)
    {
        $criteria = collect($record)->filter(function($value, $key) {
            return $this->isCriteria($key);
        });

        if($criteria->count() == 0) {
            throw new \Exception("No criteria/attributes detected");
        }

        return $criteria->mapWithKeys(function($value, $key) {
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
            if($this->isCriteria($key)) {
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
     * Check if column is criteria for a condition match
     *
     * @param string $key
     * @return boolean
     */
    protected function isCriteria($key)
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
