<?php

namespace App\Exceptions;

use Exception;

class HttpException extends Exception {
    protected $statusCode;

    public function __construct(int $statusCode, string $message = '', \Throwable $previous = null, int $code = 0) {
        $this->statusCode = $statusCode;
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }
}
