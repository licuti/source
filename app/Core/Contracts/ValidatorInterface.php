<?php

namespace App\Core\Contracts;

interface ValidatorInterface {
    public function validate(array $data, array $rules, array $messages = []): bool;
    public function fails(): bool;
    public function errors(): array;
    public function extend(string $ruleName, callable $handler, string $message = '');
}
