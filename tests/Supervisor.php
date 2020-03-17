<?php

namespace nullthoughts\LaravelDataSync\Tests;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function roles()
    {
        return $this->hasMany(Roles::class);
    }
}
