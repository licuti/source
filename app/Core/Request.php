<?php

namespace App\Core;

class Request {
    public $uri;
    public $method;
    public $params = [];       // Query string: $_GET
    public $inputs = [];       // POST body: $_POST
    public $routeParams = [];  // Route params từ URL: /san-pham/{slug}

    public function __construct() {
        $this->uri    = $this->parseUri();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->params = $_GET;
        $this->inputs = $_POST;
    }

    protected function parseUri() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Remove subdirectory if project is not at root
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }

        return '/' . trim($path, '/');
    }

    public function get($key, $default = null) {
        return $this->params[$key] ?? $default;
    }

    public function input($key, $default = null) {
        return $this->inputs[$key] ?? $this->params[$key] ?? $default;
    }

    /** Lấy route param (slug từ URL pattern) */
    public function param($key, $default = null) {
        return $this->routeParams[$key] ?? $default;
    }

    /** Được gọi bởi Router sau khi match thành công */
    public function setParams(array $params): void {
        $this->routeParams = $params;
    }

    public function all() {
        return array_merge($this->params, $this->inputs);
    }
}
