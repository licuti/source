<?php

namespace App\Core\Contracts;

interface ViewInterface {
    public function render(string $view, array $data = []): string;
    public function share(string $key, $value);
}
