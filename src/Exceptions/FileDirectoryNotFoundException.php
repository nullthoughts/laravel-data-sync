<?php

namespace nullthoughts\LaravelDataSync\Exceptions;

use Exception;

class FileDirectoryNotFoundException extends Exception
{
    protected $message = 'Specified sync file directory does not exist';
}
