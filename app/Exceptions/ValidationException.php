<?php

namespace App\Exceptions;

use Exception;
use App\Core\Contracts\ValidatorInterface;

class ValidationException extends Exception {
    protected $validator;

    public function __construct(ValidatorInterface $validator) {
        parent::__construct('The given data was invalid.');
        $this->validator = $validator;
    }

    public function getValidator(): ValidatorInterface {
        return $this->validator;
    }

    public function errors(): array {
        return $this->validator->errors();
    }
}
