<?php

namespace nullthoughts\LaravelDataSync\Tests;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }
}
