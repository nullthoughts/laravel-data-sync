<?php

namespace nullthoughts\LaravelDataSync\Exceptions;

use Exception;

class NoCriteriaException extends Exception
{
    protected $message = 'No criteria/attributes detected';
}
