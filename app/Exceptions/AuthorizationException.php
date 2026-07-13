<?php

namespace App\Exceptions;

use Exception;

class AuthorizationException extends Exception {
    public function __construct(string $message = 'This action is unauthorized.', \Throwable $previous = null, int $code = 0) {
        parent::__construct($message, $code, $previous);
    }
}
