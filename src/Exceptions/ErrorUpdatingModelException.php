<?php

namespace nullthoughts\LaravelDataSync\Exceptions;

use Exception;
use Throwable;

class ErrorUpdatingModelException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->message = "Error updating the {$message} model.";
    }
}
