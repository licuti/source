<?php

namespace App\Core\Contracts;

interface ContainerInterface {
    public function bind(string $abstract, $concrete = null, bool $shared = false);
    public function singleton(string $abstract, $concrete = null);
    public function make(string $abstract, array $parameters = []);
    public function bound(string $abstract): bool;
}
