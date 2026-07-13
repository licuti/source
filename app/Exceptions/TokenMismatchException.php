<?php

namespace App\Exceptions;

use Exception;

class TokenMismatchException extends Exception {
    public function __construct($message = "CSRF token mismatch.") {
        parent::__construct($message, 419);
    }
}
