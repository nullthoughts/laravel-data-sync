<?php

namespace nullthoughts\LaravelDataSync\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supervisor extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function roles(): HasMany
    {
        return $this->hasMany(Roles::class);
    }
}
