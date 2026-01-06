<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(string $message = 'Insufficient stock available', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

