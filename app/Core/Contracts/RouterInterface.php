<?php

namespace App\Core\Contracts;

interface RouterInterface {
    public function add(string $method, string $route, $action);
    public function get(string $route, $action);
    public function post(string $route, $action);
    public function dispatch(RequestInterface $request, ResponseInterface $response);
}
