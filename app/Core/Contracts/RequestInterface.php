<?php

namespace App\Core\Contracts;

interface RequestInterface {
    public function get(string $key, $default = null);
    public function post(string $key, $default = null);
    public function input(string $key, $default = null);
    public function all(): array;
    public function file(string $key);
    public function hasFile(string $key): bool;
    public function bearerToken(): ?string;
    public function expectsJson(): bool;
    public function ip(): string;
    public function isMethod(string $method): bool;
    public function method(): string;
    public function uri(): string;
}
