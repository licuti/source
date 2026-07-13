<?php

namespace App\Exceptions;

use Exception;

class ModelNotFoundException extends Exception {
    protected $model;

    public function setModel(string $model) {
        $this->model = $model;
        $this->message = "No query results for model [{$model}].";
        return $this;
    }

    public function getModel() {
        return $this->model;
    }
}
