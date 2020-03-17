<?php

namespace nullthoughts\LaravelDataSync\Exceptions;

use Exception;
use Throwable;

class NoRecordsInvalidJSONException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->message = "No records or invalid JSON for {$message} model.";
    }
}
